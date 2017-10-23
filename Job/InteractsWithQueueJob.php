<?php

namespace Printplanet\Component\Queue\Job;

use Printplanet\Component\Queue\PendingDispatch;
use Printplanet\Component\Queue\PendingChain;

/**
 * Class InteractsWithQueueJob
 *
 * @package Printplanet\Component\Queue\Jobs
 */
abstract class InteractsWithQueueJob
{

    /**
     * The underlying queue job instance.
     *
     * @var JobsInterface
     */
    protected $job;

    /**
     * The name of the connection the job should be sent to.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue;

    /**
     * The number of seconds before the job should be made available.
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    public $delay;

    /**
     * The jobs that should run if this job is successful.
     *
     * @var array
     */
    public $chained = array();

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->job ? $this->job->attempts() : 1;
    }

    /**
     * Delete the job from the queue.
     *
     */
    public function delete()
    {
        if ($this->job) {
            return $this->job->delete();
        }

        return null;
    }

    /**
     * Fail the job from the queue.
     *
     * @param  \Throwable  $exception
     */
    public function fail($exception = null)
    {
        if ($this->job) {
            FailingJob::handle($this->job->getConnectionName(), $this->job, $exception);
        }
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int $delay
     * @return null
     */
    public function release($delay = 0)
    {
        if ($this->job) {
            return $this->job->release($delay);
        }

        return null;
    }

    /**
     * Set the base queue job instance.
     *
     * @param  JobsInterface  $job
     * @return $this
     */
    public function setJob(JobsInterface $job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * Dispatch the job with the given arguments.
     *
     * @return PendingDispatch
     */
    public static function dispatch()
    {
        $reflect  = new \ReflectionClass(get_called_class());
        $job = $reflect->newInstanceArgs(func_get_args());

        return new PendingDispatch($job);
    }


    /**
     * Set the jobs that should run if this job is successful.
     *
     * @param  array  $chain
     * @return  PendingChain
     */
    public static function withChain($chain)
    {
        return new PendingChain(get_called_class(), $chain);
    }

    /**
     * Set the desired connection for the job.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the desired queue for the job.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Set the desired delay for the job.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * Set the jobs that should run if this job is successful.
     *
     * @param  array  $chain
     * @return $this
     */
    public function chain($chain)
    {
        $this->chained = collect($chain)->map(function ($job) {
            return serialize($job);
        })->all();

        return $this;
    }

    /**
     * Dispatch the next job on the chain.
     *
     * @return void
     */
    public function dispatchNextJobInChain()
    {
        $me = $this;
        if (! empty($this->chained)) {
            dispatch(tap(unserialize(array_shift($this->chained)), function ($next) use($me) {
                $next->chained = $me->chained;
            }));
        }
    }

}
