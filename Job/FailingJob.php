<?php

namespace Printplanet\Component\Queue\Job;

use Printplanet\Component\Queue\Exception\ManuallyFailedException;
use Printplanet\Component\Container\Container;
use Printplanet\Component\Queue\Events\JobFailed;
use Printplanet\Component\Events\Dispatcher;

class FailingJob
{
    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
     *
     * @param  string  $connectionName
     * @param  JobsInterface  $job
     * @param  \Exception $e
     */
    public static function handle($connectionName, $job, $e = null)
    {
        $job->markAsFailed();

        if ($job->isDeleted()) {
            return;
        }

        try {
            // If the job has failed, we will delete it, call the "failed" method and then call
            // an event indicating the job has failed so it can be logged if needed. This is
            // to allow every developer to better keep monitor of their failed queue jobs.
            $job->delete();

            $job->failed($e);
        } catch(\Exception $e) {
            static::events()->dispatch(new JobFailed(
                $connectionName, $job, $e ?: new ManuallyFailedException()
            ));
        }

        static::events()->dispatch(new JobFailed(
            $connectionName, $job, $e ?: new ManuallyFailedException()
        ));
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return Dispatcher
     */
    protected static function events()
    {
        return Container::getInstance()->make('events');
    }
}