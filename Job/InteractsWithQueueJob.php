<?php

namespace Printplanet\Component\Queue\Job;


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
     * @param  int   $delay
     */
    public function release($delay = 0)
    {
        if ($this->job) {
            return $this->job->release($delay);
        }
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
     * @return Printplanet\Component\Queue\PendingDispatch
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
     * @return  Printplanet\Component\Queue\PendingChain
     */
    public static function withChain($chain)
    {
        return new PendingChain(get_called_class(), $chain);
    }

}
