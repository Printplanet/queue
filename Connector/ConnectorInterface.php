<?php

namespace Printplanet\Component\Queue\Connector;

use Printplanet\Component\Queue\Type\QueueInterface;
use Printplanet\Component\Container\Container;

/**
 * Interface ConnectorInterface
 *
 * @package Printplanet\Component\Queue\Connectors
 */
interface ConnectorInterface
{
    /**
     * Establish a queue connection and return the queue instance.
     *
     * @param  Container $container The application container.
     * @param  array              $config    Connection configuration.
     *
     * @return QueueInterface The new queue instance.
     */
    public function connect(Container $container, array $config);

    /**
     * Returns the unique name of the connection.
     *
     * @return string
     */
    public function getName();
}