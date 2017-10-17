<?php

namespace PP\Component\Queue\Command;

use Monolog\Logger;
use PP\Component\Queue\Manager;
use PP\Component\Queue\Repository\FailedJobRepositoryInterface;
use PP\Component\Queue\Type\DatabaseQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RetryCommand
 *
 * @package PP\Component\Queue\Command
 */
class RetryCommand extends Command
{

    /**
     * Interface which provides necessary construct for handling the failed job.
     *
     * @var FailedJobRepositoryInterface $failed
     */
    private $failed;

    /**
     * The queue manager.
     *
     * @var Manager $queue
     */
    private $queue;

    /**
     * This function constructs the RetryCommand object.
     *
     * @param Manager                  $queue  The queue manager.
     * @param FailedJobRepositoryInterface $failed Interface which provides necessary construct for handling the failed job.
     */
    public function __construct(Manager $queue, FailedJobRepositoryInterface $failed = null)
    {
        parent::__construct();

        $this->failed = $failed;
        $this->queue = $queue;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('queue:retry')
             ->setDescription('Retry a failed queue job')
             ->addArgument('id', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The ID(s) of the failed job');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $me = $this;

        $this->fire($input, $output, function(InputInterface $input, OutputInterface $output) use($me) {

            $ids = $input->getArgument('id');

            $failedJobs = ($ids === 'all') ? $me->failed->findAll() : $me->failed->findByIds($ids);

            // If entity is found.
            if (!empty($failedJobs)) {

                foreach ($failedJobs as $failedJob) {

                    $failedJob->setPayload($me->resetAttempts($failedJob->getPayload()));

                    // Queue the job again. Here payload is an array so we need to decode it as an valid json string back
                    // again.
                    $queue = $me->queue->connection($failedJob->getConnection());

                    if ($queue instanceof DatabaseQueue) {

                        $queue->pushRaw($failedJob->getPayload(), $failedJob->getQueue());

                    } else {

                        $queue->pushRaw(json_encode($failedJob->getPayload()), $failedJob->getQueue());
                    }


                    $me->failed->forget($failedJob);
                    $output->writeln('The failed job ID #' . $failedJob->getId() . ' has been pushed back onto the queue!');
                }

            } else {

                $output->writeln('No failed job matches the given ID.');

            }

        }, $this->failed);
    }

    /**
     * Reset the attempts key in payload if it is present.
     *
     * @param  array $payload The payload.
     *
     * @return array The payload.
     */
    protected function resetAttempts($payload)
    {
        if (isset($payload['attempts'])) {

            $payload['attempts'] = 0;
        }

        return $payload;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param callable $callback
     * @param Logger|null $failed
     */
    protected function fire(InputInterface $input, OutputInterface $output, Callable $callback, Logger $failed = null)
    {
        if ($failed !== null) {

            $callback($input, $output);

        } else {

            $message = '<error>Service pp_queue.failed_repository not configured. '.
                'You need to define service in config under pp_queue -> failed_job_repository</error>';

            $output->writeln($message);
        }
    }

}