<?php

namespace Printplanet\Component\Queue\Connector;

use Printplanet\Component\Queue\Type\NullQueue;
use Printplanet\Component\Container\Container;

/**
 * Class NullConnector
 *
 * @package Printplanet\Component\Queue\Connectors
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