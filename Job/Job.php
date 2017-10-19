<?php

namespace Printplanet\Component\Queue\Job;

use Printplanet\Component\Support\Arr;
use Printplanet\Component\Container\Container;

/**
 * Class Job
 *
 * @package Printplanet\Component\Queue\Jobs
 */
abstract class Job
{
    /**
     * The job handler instance.
     *
     * @var mixed
     */
    protected $instance;

    /**
     * The IoC container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected $released = false;

    /**
     * Indicates if the job has failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * The name of the connection the job belongs to.
     */
    protected $connectionName;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected $queue;

    /**
     * Get the job identifier.
     *
     * @return string
     */
    abstract public function getJobId();

    /**
     * @return mixed
     */
    abstract public function getRawBody();

    /**
     * Fire the job.
     */
    public function fire()
    {
        $payload = $this->payload();

        list($class, $method) = JobName::parse($payload['job']);

        $this->instance = $this->resolve($class);

        $this->instance->{$method}($this, $payload['data']);
    }

    /**
     * @inheritdoc
     */
    public function payload()
    {
        return json_decode($this->getRawBody(), true);
    }

    /**
     * Resolve the given class.
     *
     * @param  string $class
     * @return mixed
     */
    protected function resolve($class)
    {
        return $this->container->make($class);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * @inheritdoc
     */
    public function release($delay = 0)
    {
        $this->released = true;
    }

    /**
     * @inheritdoc
     */
    public function isDeletedOrReleased()
    {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * @inheritdoc
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @inheritdoc
     */
    public function isReleased()
    {
        return $this->released;
    }

    /**
     * @inheritdoc
     */
    public function hasFailed()
    {
        return $this->failed;
    }

    /**
     * @inheritdoc
     */
    public function failed($e)
    {
        $this->markAsFailed();

        $payload = $this->payload();

        list($class, $method) = JobName::parse($payload['job']);

        if (method_exists($this->instance = $this->resolve($class), 'failed')) {
            $this->instance->failed($payload['data'], $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function markAsFailed()
    {
        $this->failed = true;
    }

    /**
     * @inheritdoc
     */
    public function maxTries()
    {
        return Arr::get($this->payload(), 'maxTries');
    }

    /**
     * @inheritdoc
     */
    public function timeout()
    {
        return Arr::get($this->payload(), 'timeout');
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        $payload = $this->payload();
        return $payload['job'];
    }

    /**
     * @inheritdoc
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * @inheritdoc
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get the service container instance.
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * @return string
     */
    public function resolveName()
    {
       return JobName::resolve($this->getName(), $this->payload());
    }

    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param  \DateTimeInterface  $delay
     *
     * @return int
     */
    protected function secondsUntil($delay)
    {
        return $delay instanceof \DateTimeInterface ? max(0, $delay->getTimestamp() - time()): (int) $delay;
    }
    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param  \DateTimeInterface|int  $delay
     *
     * @return int
     */
    protected function availableAt($delay = 0)
    {
        return $delay instanceof \DateTimeInterface ? $delay->getTimestamp() : (time() + $delay);
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * @return int
     */
    protected function currentTime()
    {
        return time();
    }

}
