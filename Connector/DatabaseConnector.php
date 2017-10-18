<?php

namespace Printplanet\Component\Queue\Connector;

use Printplanet\Components\Support\Arr;
use Printplanet\Component\Queue\Repository\DatabaseQueueRepositoryInterface;
use Printplanet\Component\Queue\Type\DatabaseQueue;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Printplanet\Component\Container\Container;

/**
 * Class DatabaseConnector
 *
 * @package Printplanet\Component\Queue\Connectors
 */
class DatabaseConnector implements ConnectorInterface
{
    /**
     * Database connections.
     *
     * @var DatabaseQueueRepositoryInterface
     */
    protected $database;

    /**
     * Create a new connector instance.
     *
     * @param  DatabaseQueueRepositoryInterface  $database
     */
    public function __construct(DatabaseQueueRepositoryInterface $database = null)
    {
        $this->database = $database;
    }

    /**
     * @inheritDoc
     */
    public function connect(Container $container, array $config)
    {
        if ($this->database === null) {

            throw new InvalidConfigurationException('In order to use database driver for the queue you must configure repository.');
        }
        $instance = new DatabaseQueue(
            $this->database,
            $config['queue'],
            Arr::get($config, 'retry_after', 60)
        );
        return $instance->setContainer($container);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'database';
    }
}
