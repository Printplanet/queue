<?php

namespace Printplanet\Component\Queue\Command;

use Printplanet\Component\Queue\Manager;
use Printplanet\Component\Queue\Util\SwitchInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class QueueStopCommand
 *
 * @package Printplanet\Component\Queue\Command
 */
class QueueStopCommand extends Command
{
    /**
     * @var SwitchInterface
     */
    private $switchInterface;

    /**
     * QueueStartCommand constructor.
     *
     * @param SwitchInterface $switchInterface
     */
    public function __construct(SwitchInterface $switchInterface)
    {
        $this->switchInterface = $switchInterface;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('queue:worker-stop')->setDescription('Turn off the queue workers.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->switchInterface->turnOn(Manager::LOCK_NAME);
        $output->writeln('Queue worker stopped successfully');
    }
}
