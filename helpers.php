<?php

use Printplanet\Component\Queue\PendingDispatch;

if (! function_exists('dispatch')) {
    /**
     * Dispatch a job to its appropriate handler.
     *
     * @param  mixed  $job
     * @return PendingDispatch
     */
    function dispatch($job)
    {
        return new PendingDispatch($job);
    }
}
