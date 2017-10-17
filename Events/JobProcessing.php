<?php

namespace PP\Component\Queue\Events;

use PP\Component\Queue\Job\JobsInterface;

class JobProcessing
{

    const CLASSNAME = __CLASS__;

    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job instance.
     *
     * @var JobsInterface
     */
    public $job;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  JobsInterface  $job
     */
    public function __construct($connectionName, $job)
    {
        $this->job = $job;
        $this->connectionName = $connectionName;
    }
}
