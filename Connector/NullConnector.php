<?php

namespace PP\Component\Queue\Connector;

use PP\Component\Queue\Type\NullQueue;
use PP\Component\Container\Container;

/**
 * Class NullConnector
 *
 * @package PP\Component\Queue\Connectors
 */
class NullConnector implements ConnectorInterface
{
    /**
     * @inheritDoc
     */
    public function connect(Container $container, array $config)
    {
        return new NullQueue;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'null';
    }
}