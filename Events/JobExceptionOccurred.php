<?php

namespace PP\Component\Queue\Events;

use PP\Component\Queue\Job\JobsInterface;

class JobExceptionOccurred
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
     * The exception instance.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  string $connectionName
     * @param  JobsInterface $job
     * @param  \Exception $exception
     */
    public function __construct($connectionName, $job, $exception)
    {
        $this->job = $job;
        $this->exception = $exception;
        $this->connectionName = $connectionName;
    }
}
