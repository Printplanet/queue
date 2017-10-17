<?php

namespace PP\Component\Queue\Connector;

use PP\Component\Queue\Type\QueueInterface;
use PP\Component\Container\Container;

/**
 * Interface ConnectorInterface
 *
 * @package PP\Component\Queue\Connectors
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