<?php

namespace Printplanet\Component\Queue\Job;

use Printplanet\Component\Support\Arr;
use Printplanet\Component\Queue\Type\RedisQueue;
use Printplanet\Component\Container\Container;

/**
 * Class RedisJob
 *
 * @package Printplanet\Component\Queue\Jobs
 */
class RedisJob extends Job implements JobsInterface
{
    /**
     * The Redis queue instance.
     *
     * @var RedisQueue
     */
    protected $redis;
    /**
     * The Redis raw job payload.
     *
     * @var string
     */
    protected $job;
    /**
     * The JSON decoded version of "$job".
     *
     * @var array
     */
    protected $decoded;
    /**
     * The Redis job payload inside the reserved queue.
     *
     * @var string
     */
    protected $reserved;

    /**
     * Create a new job instance.
     *
     * @param  Container $container
     * @param  RedisQueue         $redis
     * @param  string             $job
     * @param  string             $reserved
     * @param  string             $queue
     */
    public function __construct(Container $container, RedisQueue $redis, $job, $reserved, $queue)
    {
        // The $job variable is the original job JSON as it existed in the ready queue while
        // the $reserved variable is the raw JSON in the reserved queue. The exact format
        // of the reserved job is required in order for us to properly delete its value.
        $this->job = $job;
        $this->redis = $redis;
        $this->queue = $queue;
        $this->reserved = $reserved;
        $this->container = $container;
        $this->decoded = $this->payload();
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();
        $this->redis->deleteReserved($this->queue, $this);
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int $delay
     *
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);
        $this->redis->deleteAndRelease($this->queue, $this, $delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return Arr::get($this->decoded, 'attempts') + 1;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return Arr::get($this->decoded, 'id');
    }

    /**
     * Get the underlying Redis factory implementation.
     *
     * @return RedisQueue
     */
    public function getRedisQueue()
    {
        return $this->redis;
    }

    /**
     * Get the underlying reserved Redis job.
     *
     * @return string
     */
    public function getReservedJob()
    {
        return $this->reserved;
    }
}