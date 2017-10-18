<?php

namespace Printplanet\Component\Queue\Connector;

use Printplanet\Components\Support\Arr;
use Printplanet\Component\Queue\Type\Sqs1Queue;
use Printplanet\Component\Container\Container;
use Aws\Sqs\SqsClient;

/**
 * Class Sqs1Connector
 *
 * @package Printplanet\Component\Queue\Connector
 */
class Sqs1Connector implements ConnectorInterface
{
    /**
     * @inheritDoc
     */
    public function connect(Container $container, array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] && $config['secret']) {

            $config['credentials'] = Arr::only($config, array('key', 'secret'));
        }

        /**
         * @todo switching to old sqs client
         */
        $client = SqsClient::factory(array(
            'key' => $config['key'],
            'secret' => $config['secret'],
            'region'  => $config['region']
        ));
        $instance = new Sqs1Queue(
            $client , $config['queue']
        );

        return $instance->setContainer($container);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'sqs1';
    }

    /**
     * Get the default configuration for SQS.
     *
     * @param  array  $config
     * @return array
     */
    protected function getDefaultConfiguration(array $config)
    {
        return array_merge(array('version' => 'latest', 'http' => array('timeout' => 60, 'connect_timeout' => 60)), $config);
    }
}
