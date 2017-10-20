<?php

namespace Printplanet\Component\Queue\Command;

use Monolog\Logger;
use Printplanet\Component\Queue\Repository\FailedJobRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ForgetFailedCommand
 *
 * @package Printplanet\Component\Queue\Command
 */
class ForgetFailedCommand extends Command
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
        $this->setName('queue:forget')
             ->setDescription('Delete a failed queue job')
             ->addArgument('id', InputArgument::REQUIRED, 'The ID of the failed job');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $me = $this;
        $this->fire($input, $output, function(InputInterface $input, OutputInterface $output) use($me) {

            $id = $input->getArgument('id');

            $failedJob = $me->failed->findByIds(array($id));

            if (empty($failedJob)) {

                $output->writeln('<error>No Job found under that ID #'. $id .'</error>');
            }

            $me->failed->forget($failedJob[0]);
            $output->writeln('Job deleted successfully.');

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
