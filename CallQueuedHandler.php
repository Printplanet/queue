<?php

namespace Printplanet\Component\Queue;


use Printplanet\Component\Queue\Job\FailingJob;
use Printplanet\Component\Queue\Job\JobsInterface as Job;
use Printplanet\Component\Bus\Dispatcher;
use Printplanet\Component\Queue\Job\JobsInterface;

class CallQueuedHandler
{
    /**
     * The bus dispatcher implementation.
     *
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Create a new handler instance.
     *
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }


    /**
     * Handle the queued job.
     *
     * @param  Job  $job
     * @param  array  $data
     */
    public function call(Job $job, array $data)
    {
        try {
            $command = $this->setJobInstanceIfNecessary(
                $job, unserialize($data['command'])
            );
        } catch (\Exception $e) {
            return $this->handleModelNotFound($job, $e);
        }

        $this->dispatcher->dispatchNow(
            $command, $this->resolveHandler($job, $command)
        );

        if (! $job->hasFailed() && ! $job->isReleased()) {
            $this->ensureNextJobInChainIsDispatched($command);
        }

        if (! $job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * Resolve the handler for the given command.
     *
     * @param  Job  $job
     * @param  mixed  $command
     * @return mixed
     */
    protected function resolveHandler($job, $command)
    {
        $handler = $this->dispatcher->getCommandHandler($command) ?: null;

        if ($handler) {
            $this->setJobInstanceIfNecessary($job, $handler);
        }

        return $handler;
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  Job  $job
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {

        if ($instance instanceof ShouldQueue) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Ensure the next job in the chain is dispatched if applicable.
     *
     * @param  mixed  $command
     * @return void
     */
    protected function ensureNextJobInChainIsDispatched($command)
    {
        if (method_exists($command, 'dispatchNextJobInChain')) {
            $command->dispatchNextJobInChain();
        }
    }

    /**
     * Handle a model not found exception.
     *
     * @param  JobsInterface  $job
     * @param  \Exception  $e
     */
    protected function handleModelNotFound(JobsInterface $job, $e)
    {
        $class = $job->resolveName();

        try {
            $reflection = new \ReflectionClass($class);
            $defaultProperties = $reflection->getDefaultProperties();
            $shouldDelete = isset($defaultProperties['deleteWhenMissingModels']) ? $defaultProperties['deleteWhenMissingModels'] : false;
        } catch (\Exception $e) {
            $shouldDelete = false;
        }

        if ($shouldDelete) {
            return $job->delete();
        }

        FailingJob::handle(
            $job->getConnectionName(), $job, $e
        );
    }

    /**
     * Call the failed method on the job instance.
     *
     * The exception that caused the failure will be passed.
     *
     * @param  array  $data
     * @param  \Exception  $e
     * @return void
     */
    public function failed(array $data, $e)
    {
        $command = unserialize($data['command']);

        if (method_exists($command, 'failed')) {
            $command->failed($e);
        }
    }
}