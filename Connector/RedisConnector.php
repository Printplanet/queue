<?php

namespace Printplanet\Component\Queue\Connector;

use Printplanet\Component\Support\Arr;
use Printplanet\Component\Queue\Exception\BadConnectionException;
use Printplanet\Component\Queue\Type\RedisQueue;
use Predis\Client;
use Printplanet\Component\Container\Container;

/**
 * Class RedisConnector
 *
 * @package Printplanet\Component\Queue\Connectors
 */
class RedisConnector implements ConnectorInterface
{
    /**
     * The Redis database instance.
     *
     * @var Client
     */
    protected $redis;

    /**
     * Create a new Redis queue connector instance.
     *
     * @param  Client  $redis
     */
    public function __construct(Client $redis = null)
    {
        $this->redis = $redis;
    }

    /**
     * Establish a queue connection.
     *
     * @param Container $container
     * @param array              $config
     *
     * @throws BadConnectionException
     *
     * @return RedisQueue
     */
    public function connect(Container $container, array $config)
    {
        if ($this->redis === null) {

            throw new BadConnectionException('Redis client not configured');
        }

        $instance = new RedisQueue(
            $this->redis,
            Arr::get($config, 'queue'),
            Arr::get($config, 'retry_after', 60)
        );
        return $instance->setContainer($container);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'redis';
    }


}
