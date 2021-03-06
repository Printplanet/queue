<?php

namespace Printplanet\Component\Queue\Command;

use Monolog\Logger;
use Printplanet\Component\Queue\Repository\FailedJobRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FlushFailedCommand
 *
 * @package Printplanet\Component\Queue\Command
 */
class FlushFailedCommand extends Command
{

    /**
     * Interface which provides necessary construct for handling the failed job.
     *
     * @var FailedJobRepositoryInterface $failed
     */
    private $failed;

    /**
     * This function constructs the ForgetFailedCommand object.
     *
     * @param FailedJobRepositoryInterface $failed A contract which defines method that must be implement by any Failed
     *                                         job provider.
     */
    public function __construct(FailedJobRepositoryInterface $failed = null)
    {
        $this->failed = $failed;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('queue:flush')->setDescription('Flush all of the failed queue jobs');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $me = $this;
        $this->fire($input, $output, function(InputInterface $input, OutputInterface $output) use($me) {

            $qtyOfDeletedJob = $me->failed->flush();

            // Affected quantity is more than 0.
            if ($qtyOfDeletedJob > 0) {

                $output->writeln($qtyOfDeletedJob . ' job deleted successfully.');

            } else {

                $output->writeln('No jobs were deleted.');

            }

        }, $this->failed);
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
