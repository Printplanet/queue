<?php

namespace Printplanet\Component\Queue\Job;

use Printplanet\Component\Queue\Entity\DatabaseQueueEntityInterface;
use Printplanet\Component\Queue\Type\DatabaseQueue;
use Printplanet\Component\Container\Container;

/**
 * Class DatabaseJob
 *
 * @package Printplanet\Component\Queue\Jobs
 */
class DatabaseJob extends Job implements JobsInterface
{
    /**
     * The database queue instance.
     *
     * @var DatabaseQueue
     */
    protected $database;

    /**
     * The database job payload.
     *
     * @var DatabaseQueueEntityInterface
     */
    protected $job;

    /**
     * Create a new job instance.
     *
     * @param Container           $container
     * @param DatabaseQueue                $database
     * @param DatabaseQueueEntityInterface $job
     * @param                              $connectionName
     * @param                              $queue
     */
    public function __construct(Container $container, DatabaseQueue $database, DatabaseQueueEntityInterface $job, $connectionName, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->database = $database;
        $this->container = $container;
        $this->connectionName = $connectionName;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int $delay
     *
     * @return mixed
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->delete();

        return $this->database->release($this->queue, $this->job, $delay);
    }

    /**
     * Delete the job from the queue.
     */
    public function delete()
    {
        parent::delete();

        $this->database->deleteReserved($this->queue, $this->job->getId());
    }

    /**
     * @inheritdoc
     */
    public function payload()
    {
        return $this->getRawBody();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int) $this->job->getAttempts();
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->getId();
    }

    /**
     * Get the raw body string for the job.
     *
     * @return array
     */
    public function getRawBody()
    {
        return $this->job->getPayload();
    }
}