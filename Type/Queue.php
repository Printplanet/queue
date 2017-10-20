<?php

namespace Printplanet\Component\Queue\Type;

use DateTimeInterface;
use Printplanet\Component\Support\Encrypter;
use Printplanet\Component\Queue\Exception\InvalidPayloadException;
use Printplanet\Component\Container\Container as Container;

/**
 * Class Queue
 *
 * @package Printplanet\Component\Queue\Types
 */
abstract class Queue
{

    /**
     * The IoC container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * The encrypter implementation.
     *
     * @var Encrypter
     */
    protected $encrypter;

    /**
     * The connection name for the queue.
     *
     * @var string
     */
    protected $connectionName;

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     *
     * @return mixed
     */
    abstract public function push($job, $data = '', $queue = null);

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  mixed   $data
     *
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     *
     * @return mixed
     */
    abstract public function later($delay, $job, $data = '', $queue = null);

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  string  $queue
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     *
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     *
     * @return void
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ($jobs as $job) {

            $this->push($job, $data, $queue);
        }
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     *
     * @return string
     *
     * @throws InvalidPayloadException
     */
    protected function createPayload($job, $data = '')
    {
        $payload = json_encode($this->createPayloadArray($job, $data));

        if (JSON_ERROR_NONE !== json_last_error()) {

            throw new InvalidPayloadException(
                'Unable to JSON encode payload. Error code: '.json_last_error()
            );
        }

        return $payload;
    }

    /**
     * Create a payload array from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     *
     * @return array
     */
    protected function createPayloadArray($job, $data = '')
    {
        return is_object($job)
            ? $this->createObjectPayload($job)
            : $this->createStringPayload($job, $data);
    }

    /**
     * Create a payload for an object-based queue handler.
     *
     * @param  mixed  $job
     * @return array
     */
    protected function createObjectPayload($job)
    {
        return array(
            'displayName' => $this->getDisplayName($job),
            'job' => 'Printplanet\Component\Queue\CallQueuedHandler@call',
            'maxTries' => $this->getMaxTries($job),
            'timeout' => $this->getTimeout($job),
            'timeoutAt' => $this->getJobExpiration($job),
            'data' => array(
                'commandName' => get_class($job),
                'command' => serialize(clone $job),
            ),
        );
    }

    /**
     * Get the display name for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    protected function getDisplayName($job)
    {
        return method_exists($job, 'displayName')
            ? $job->displayName() : get_class($job);
    }


    /**
     * Get the display name for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    protected function getTimeout($job)
    {
        if (! method_exists($job, 'getTimeout') && ! isset($job->timeout)) {
            return null;
        }
        return method_exists($job, 'getTimeout')
            ? $job->getTimeout() : $job->timeout;
    }

    /**
     * Get the display name for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    protected function getMaxTries($job)
    {
        if (! method_exists($job, 'getMaxTries') && ! isset($job->maxTries)) {
            return null;
        }
        return method_exists($job, 'getMaxTries')
            ? $job->getMaxTries() : $job->maxTries;
    }


    /**
     * Get the expiration timestamp for an object-based queue handler.
     *
     * @param  mixed  $job
     * @return mixed
     */
    public function getJobExpiration($job)
    {
        if (! method_exists($job, 'retryUntil') && ! isset($job->timeoutAt)) {
            return null;
        }
        $expiration = isset($job->timeoutAt) ? $job->timeoutAt : $job->retryUntil();

        return $expiration instanceof DateTimeInterface
            ? $expiration->getTimestamp() : $expiration;
    }

    /**
     * Create a typical, string based queue payload array.
     *
     * @param  string  $job
     * @param  mixed  $data
     *
     * @return array
     */
    protected function createStringPayload($job, $data)
    {
        $jobName = explode('@', $job);
        return array(
            'displayName' => is_string($job) ? $jobName[0] : null,
            'job' => $job,
            'maxTries' => null,
            'timeout' => null,
            'data' => $data,
        );
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->connectionName = $name;

        return $this;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  Container  $container
     *
     * @return static
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
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
