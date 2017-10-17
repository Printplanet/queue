<?php

namespace PP\Component\Queue\Connector;

use PP\Component\Queue\Type\BeanstalkdQueue as Queue;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Connection;
use Pheanstalk\PheanstalkInterface;
use PP\Utils\Arr;
use PP\Component\Container\Container;

/**
 * Class BeanstalkdConnector
 *
 * @package PP\Component\Queue\Connector
 */
class BeanstalkdConnector implements ConnectorInterface
{
    /**
     * @inheritDoc
     */
    public function connect(Container $container, array $config)
    {
        $retryAfter = Arr::get($config, 'retry_after', Pheanstalk::DEFAULT_TTR);
        $instance = new Queue($this->pheanstalk($config), $config['queue'], $retryAfter);
        return $instance->setContainer($container);
    }

    /**
     * Create a Pheanstalk instance.
     *
     * @param  array  $config
     *
     * @return \Pheanstalk\Pheanstalk
     */
    protected function pheanstalk(array $config)
    {
        $port = Arr::get($config, 'port', PheanstalkInterface::DEFAULT_PORT);
        $timeout = Arr::get($config, 'timeout', Connection::DEFAULT_CONNECT_TIMEOUT);
        $persistent = Arr::get($config, 'persistent', false);

        return new Pheanstalk($config['host'], $port, $timeout, $persistent);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'beanstalkd';
    }
}