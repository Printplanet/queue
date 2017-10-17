<?php

namespace PP\Component\Queue\Type;


use PP\Component\Queue\Job\FailingJob;
use PP\Component\Queue\Job\SyncJob;
use PP\Component\Queue\Job\JobsInterface;
use PP\Component\Events\Dispatcher;
use PP\Component\Queue\Events;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * Class SyncQueue
 *
 * @package PP\Component\Queue\Type
 */
class SyncQueue extends Queue implements QueueInterface
{
    /**
     * The event dispatcher instance.
     *
     * @var Dispatcher
     */
    protected $events;

    /**
     * SyncQueue constructor.
     *
     * @param Dispatcher $events
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function push($job, $data = '', $queue = null)
    {
        $queueJob = $this->resolveJob($this->createPayload($job, $data), $queue);

        try {

            $this->raiseBeforeJobEvent($queueJob);

            $queueJob->fire();

            $this->raiseAfterJobEvent($queueJob);
        } catch (\Exception $e) {
            $this->handleException($queueJob, $e);
        } catch (\Throwable $e) {
            $this->handleException($queueJob, new FatalThrowableError($e));
        }

        return 0;
    }

    /**
     * Resolve a Sync job instance.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @return SyncJob
     */
    protected function resolveJob($payload, $queue)
    {
        return new SyncJob($this->container, $payload, $this->connectionName, $queue);
    }

    /**
     * Raise the before queue job event.
     *
     * @param  JobsInterface  $job
     * @return void
     */
    protected function raiseBeforeJobEvent(JobsInterface $job)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->dispatch(new Events\JobProcessing($this->connectionName, $job));
        }
    }

    /**
     * Raise the after queue job event.
     *
     * @param  JobsInterface  $job
     * @return void
     */
    protected function raiseAfterJobEvent(JobsInterface $job)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->dispatch(new Events\JobProcessed($this->connectionName, $job));
        }
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param  JobsInterface  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent(JobsInterface $job, $e)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->dispatch(new Events\JobExceptionOccurred($this->connectionName, $job, $e));
        }
    }

    /**
     * Handle an exception that occurred while processing a job.
     *
     * @param  JobsInterface  $queueJob
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleException($queueJob, $e)
    {
        $this->raiseExceptionOccurredJobEvent($queueJob, $e);

        FailingJob::handle($this->connectionName, $queueJob, $e);

        throw $e;
    }


    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = array())
    {
        //
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
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     *
     * @return JobsInterface|null
     */
    public function pop($queue = null)
    {
        //
    }
}