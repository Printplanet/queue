<?php

namespace PP\Component\Queue\Connector;

use PP\Component\Queue\Type\SyncQueue;
use PP\Component\Container\Container;
use PP\Component\Events\Dispatcher;

/**
 * Class SyncConnectors
 *
 * @package PP\Component\Queue\Connectors
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