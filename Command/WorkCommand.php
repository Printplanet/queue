<?php

namespace Printplanet\Component\Queue\Command;

use Printplanet\Component\Queue\Events\JobFailed;
use Printplanet\Component\Queue\Events\JobProcessed;
use Printplanet\Component\Queue\Events\JobProcessing;
use Printplanet\Component\Queue\Job\Job;
use Printplanet\Component\Queue\Manager;
use Printplanet\Component\Queue\Repository\FailedJobRepositoryInterface;
use Printplanet\Component\Queue\Util\SwitchInterface;
use Printplanet\Component\Queue\Worker;
use Printplanet\Component\Queue\WorkerOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Printplanet\Component\Events\Dispatcher;

/**
 * Class WorkCommand which implements the logic of idb-queue:work command. This command executes the worker to do the job.
 *
 * @package PriceMonkey\Bundle\QueueBundle\Command
 */
class WorkCommand extends Command
{
    /**
     * The worker instance.
     *
     * @var Worker $worker
     */
    private $worker;

    /**
     * The system utility.
     *
     * @var SwitchInterface
     */
    private $switch;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Dispatcher
     */
    private $event;

    /**
     * @var FailedJobRepositoryInterface
     */
    private $failedJobLogger;

    /**
     * The queue configuration.
     *
     * @var array $queueConfiguration
     */
    private $queueConfiguration;

    /**
     * This function constructs the WorkCommand object.
     *
     * @param Worker                   $worker Queue worker.
     * @param SwitchInterface          $system The system utility.
     * @param Dispatcher $event
     * @param CacheInterface           $cache
     * @param array                    $queueConfiguration
     */
    public function __construct(Worker $worker, SwitchInterface $system, Dispatcher $event, CacheInterface $cache, array $queueConfiguration)
    {
        $this->worker = $worker;
        $this->cache = $cache;
        $this->switch = $system;
        $this->event = $event;
        $this->queueConfiguration = $queueConfiguration;

        parent::__construct();
    }

    /**
     * @param FailedJobRepositoryInterface $failedJobLogger
     *
     * @return WorkCommand
     */
    public function setFailedJobLogger($failedJobLogger = null)
    {
        $this->failedJobLogger = $failedJobLogger;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('queue:work')
             ->setDescription('Start processing jobs on the queue as a daemon')
             ->addArgument('connection', InputArgument::OPTIONAL, 'The name of connection')
             ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on', null)
             ->addOption('once', null, InputOption::VALUE_NONE, 'Only process the next job on the queue')
             ->addOption('delay', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0)
             ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'Seconds a job may run before timing out', 60)
             ->addOption('force', null, InputOption::VALUE_NONE, 'Force the worker to run even in maintenance mode')
             ->addOption('memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128)
             ->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3)
             ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->downForMaintenance($input->getOption('force')) && $input->getOption('once')) {

            $this->worker->sleep($input->getOption('sleep'));
        }

        $io = new SymfonyStyle($input, $output);

        $connection = $input->getArgument('connection');

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queue = $this->getQueue($input->getOption('queue'), $connection);


        // We'll listen to the processed and failed events so we can write information
        // to the console as jobs are processed, which will let the developer watch
        // which jobs are coming through a queue and be informed on its progress.
        $this->listenForEvents($io);


        if ($queue === null) {

            $output->writeln('<error>Queue name cannot be null also you cannot listen Sync and Null queue.</error>');

        } else {

            $this->runWorker($connection, $queue, $input);
        }
    }

    /**
     * Run the worker instance.
     *
     * @param string         $connection
     * @param string         $queue
     * @param InputInterface $input
     *
     * @return array
     */
    protected function runWorker($connection, $queue, InputInterface $input)
    {
        $this->worker->setCache($this->cache);

        return $this->worker->{$input->getOption('once') ? 'runNextJob' : 'daemon'}(
            $connection, $queue, $this->gatherWorkerOptions($input)
        );
    }

    /**
     * Gather all of the queue worker options as a single object.
     *
     * @param InputInterface $input
     *
     * @return WorkerOptions
     */
    protected function gatherWorkerOptions(InputInterface $input)
    {
        return new WorkerOptions(
            $input->getOption('delay'), $input->getOption('memory'),
            $input->getOption('timeout'), $input->getOption('sleep'),
            $input->getOption('tries'), $input->getOption('force')
        );
    }

    /**
     * Listen for the queue events in order to update the console output.
     *
     * @param SymfonyStyle $io
     *
     * @return void
     */
    protected function listenForEvents(SymfonyStyle $io)
    {
        $me = $this;

        $this->event->listen(JobProcessing::CLASSNAME, function ($event) use ($io, $me) {
            $io->note($me->formatMessage($event->job, 'Processing'));
        });

        $this->event->listen(JobProcessed::CLASSNAME, function($event) use ($io, $me) {
            $io->success($me->formatMessage($event->job, 'Processed'));
        });

        $this->event->listen(JobFailed::CLASSNAME, function($event) use ($io, $me) {
            $io->error($me->formatMessage($event->job, 'Failed'));

            $me->logFailedJob($event);
        });
    }

    /**
     * Formats a console message
     *
     * @internal This method has to be public because of the limitation of php 5.3
     * in calling protected/private methods inside of a closure
     *
     * @param Job $job
     * @param string $status
     * @return string
     */
    public function formatMessage(Job $job, $status)
    {
        return sprintf(
            "[%s][%s] %s %s",
            date('Y-m-d H:i:s'),
            $job->getJobId(),
            str_pad("{$status}:", 11), $job->resolveName()
        );
    }

    /**
     * Store a failed job event.
     *
     * @internal This method has to be public because of the limitation of php 5.3
     * in calling protected/private methods inside of a closure
     *
     * @param  JobFailed $event
     *
     * @return void
     */
    public function logFailedJob(JobFailed $event)
    {
        if ($this->failedJobLogger !== null) {

            $this->failedJobLogger->log(
                $event->connectionName,
                $event->job->getQueue(),
                $event->job->getRawBody(),
                $event->exception
            );
        }
    }

    /**
     * Get the queue name for the worker.
     *
     * @param  string $queueName
     * @param  string $connection
     *
     * @return string
     */
    protected function getQueue($queueName, $connection)
    {
        if ($queueName !== null) {

            return $queueName;
        }

        if ($connection === null) {

            $queue = $this->queueConfiguration['default'];

            if (($queue === 'sync') || ($queue === 'null')) {

                return null;
            }

            return $this->queueConfiguration['connections'][$queue]['queue'];

        } elseif ($connection !== null) {

            if (($connection === 'sync') || ($connection === 'null')) {

                return null;
            }

            return $this->queueConfiguration['connections'][$connection]['queue'];
        }

        return null;
    }

    /**
     * Determine if the worker should run in maintenance mode.
     *
     * @param $force
     *
     * @return bool
     */
    protected function downForMaintenance($force)
    {
        return !empty($force) ? false : $this->switch->isOn(Manager::LOCK_NAME);
    }
}
