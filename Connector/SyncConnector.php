<?php

namespace Printplanet\Component\Queue\Connector;

use Printplanet\Component\Queue\Type\SyncQueue;
use Printplanet\Component\Container\Container;
use Printplanet\Component\Events\Dispatcher;

/**
 * Class SyncConnectors
 *
 * @package Printplanet\Component\Queue\Connectors
 */
class SyncConnector implements ConnectorInterface
{
    /**
     * @inheritDoc
     */
    public function connect(Container $container, array $config)
    {


        /** @var Dispatcher $events */
        $events = $container->make('events');

        $instance = new SyncQueue($events);

        return $instance->setContainer($container);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'sync';
    }
}