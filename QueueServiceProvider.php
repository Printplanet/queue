<?php

namespace Printplanet\Component\Queue;

use Printplanet\Component\Container\Container;
use Printplanet\Component\Contracts\Queue\Factory;
use Printplanet\Component\Queue\Connector\BeanstalkdConnector;
use Printplanet\Component\Queue\Connector\DatabaseConnector;
use Printplanet\Component\Queue\Connector\NullConnector;
use Printplanet\Component\Queue\Connector\RedisConnector;
use Printplanet\Component\Queue\Connector\Sqs1Connector;
use Printplanet\Component\Queue\Connector\SqsConnector;
use Printplanet\Component\Queue\Connector\SyncConnector;
use Printplanet\Component\Queue\Failed\DatabaseFailedJobProvider;
use Printplanet\Component\Queue\Failed\NullFailedJobProvider;
use Printplanet\Component\Queue\Manager as QueueManager;
use Printplanet\Component\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerManager();

        $this->registerConnection();

        $this->registerWorker();

        $this->registerListener();

        $this->registerFailedJobServices();
    }

    /**
     * Register the queue manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $me = $this;

        $this->app->singleton('queue', function ($app) use($me) {

            $manager = new QueueManager(
                $app,
                $app['queue.configuration'],
                $app['queue.switch_service']
            );

            $me->registerConnectors($manager);

            return $manager;
        });

        $this->app->alias(
            'queue', Factory::CLASSNAME
        );

    }

    /**
     * Register the default queue connection binding.
     *
     * @return void
     */
    protected function registerConnection()
    {
        $this->app->singleton('queue.connection', function ($app) {
            return $app['queue']->connection();
        });
    }

    /**
     * Register the connectors on the queue manager.
     *
     * @param  QueueManager $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        foreach (array('Null', 'Sync', 'Database', 'Redis', 'Beanstalkd', 'Sqs', 'Sqs1') as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }

    /**
     * Register the Null queue connector.
     *
     * @param  QueueManager  $manager
     * @return void
     */
    protected function registerNullConnector($manager)
    {
        $manager->addConnector(new NullConnector);
    }

    /**
     * Register the Sync queue connector.
     *
     * @param  QueueManager  $manager
     * @return void
     */
    protected function registerSyncConnector($manager)
    {
        $manager->addConnector(new SyncConnector);
    }

    /**
     * Register the database queue connector.
     *
     * @param  QueueManager  $manager
     * @return void
     */
    protected function registerDatabaseConnector($manager)
    {
        $connector = (isset($this->app['db'])) ? $this->app['db'] : null;
        $manager->addConnector(new DatabaseConnector($connector));
    }

    /**
     * Register the Redis queue connector.
     *
     * @param  QueueManager  $manager
     * @return void
     */
    protected function registerRedisConnector($manager)
    {
        $connector = (isset($this->app['redis'])) ? $this->app['redis'] : null;
        $manager->addConnector(new RedisConnector($connector));
    }

    /**
     * Register the Beanstalkd queue connector.
     *
     * @param  QueueManager  $manager
     * @return void
     */
    protected function registerBeanstalkdConnector($manager)
    {
        $manager->addConnector(new BeanstalkdConnector);
    }

    /**
     * Register the Amazon SQS queue connector.
     *
     * @param  QueueManager  $manager
     * @return void
     */
    protected function registerSqsConnector($manager)
    {
        $manager->addConnector(new SqsConnector);
    }

    /**
     * Register the Amazon SQS queue connector.
     *
     * @param  QueueManager  $manager
     * @return void
     */
    protected function registerSqs1Connector($manager)
    {
        $manager->addConnector(new Sqs1Connector);
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerWorker()
    {
        $this->app->singleton('queue.worker', function($app) {
            /** @var Container $app */
            return new Worker(
                $app->make('queue'), $app->make('events'), $app->make('queue.exception_handler')
            );
        });
    }

    /**
     * Register the queue listener.
     *
     * @return void
     */
    protected function registerListener()
    {
        $this->app->singleton('queue.listener', function ($app) {
            return new Listener($app['path.command']);
        });
    }

    /**
     * Register the failed job services.
     *
     * @return void
     */
    protected function registerFailedJobServices()
    {
        $me = $this;

        $this->app->singleton('queue.failer', function ($app) use($me) {
            $config = $app['config']['queue.failed'];

            return isset($config['table'])
                ? $me->databaseFailedJobProvider($config)
                : new NullFailedJobProvider;
        });
    }

    /**
     * Create a new database failed job provider.
     *
     * @param  array  $config
     * @return DatabaseFailedJobProvider
     */
    protected function databaseFailedJobProvider($config)
    {
        return new DatabaseFailedJobProvider(
            $this->app['db'], $config['database'], $config['table']
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'queue', 'queue.worker', 'queue.listener',
            'queue.failer', 'queue.connection',
        );
    }
}
