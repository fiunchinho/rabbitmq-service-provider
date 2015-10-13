<?php

namespace fiunchinho\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\AnonConsumer;
use OldSound\RabbitMqBundle\RabbitMq\MultipleConsumer;
use OldSound\RabbitMqBundle\RabbitMq\RpcClient;
use OldSound\RabbitMqBundle\RabbitMq\RpcServer;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;

class RabbitServiceProvider implements ServiceProviderInterface
{
    const DEFAULT_CONNECTION = 'default';

    public function register(Application $app)
    {
        $this->loadConnections($app);
        $this->loadProducers($app);
        $this->loadConsumers($app);
        $this->loadAnonymousConsumers($app);
        $this->loadMultipleConsumers($app);
        $this->loadRpcClients($app);
        $this->loadRpcServers($app);
    }

    public function boot(Application $app)
    {
    }

    /**
     * Return the name of the connection to use.
     * 
     * @param  array     $options     Options for the Producer or Consumer.
     * @param  array     $connections Connections defined in the config file.
     * @return string                 The connection name that will be used
     */
    private function getConnection($app, $options, $connections)
    {
        $connection_name = @$options['connection']?: self::DEFAULT_CONNECTION;

        if (!isset($connections[$connection_name])) {
            throw new \InvalidArgumentException('Configuration for connection [' . $connection_name . '] not found');
        }

        return $app['rabbit.connection'][$connection_name];
    }

    private function loadConnections($app)
    {
        $app['rabbit.connection'] = $app->share(function ($app) {
            if (!isset($app['rabbit.connections'])) {
                throw new \InvalidArgumentException('You need to specify at least a connection in your configuration.');
            }

            $connections = [];
            foreach ($app['rabbit.connections'] as $name => $options) {
                $connection = new AMQPLazyConnection(
                    $app['rabbit.connections'][$name]['host'],
                    $app['rabbit.connections'][$name]['port'],
                    $app['rabbit.connections'][$name]['user'],
                    $app['rabbit.connections'][$name]['password'],
                    $app['rabbit.connections'][$name]['vhost']
                );

                $connections[$name] = $connection;
            }

            return $connections;
        });
    }

    private function loadProducers($app)
    {
        $app['rabbit.producer'] = $app->share(function ($app) {
            if (!isset($app['rabbit.producers'])) {
                return;
            }

            $producers = [];
            foreach ($app['rabbit.producers'] as $name => $options) {
                $connection = $this->getConnection($app, $options, $app['rabbit.connections']);

                $producer = new Producer($connection);
                $producer->setExchangeOptions($options['exchange_options']);
		$producer->setQueueOptions($options['queue_options']);

                //this producer doesn't define a queue
                if (!isset($options['queue_options'])) {
                    $options['queue_options']['name'] = null;
                }
                $producer->setQueueOptions($options['queue_options']);

                if ((array_key_exists('auto_setup_fabric', $options)) && (!$options['auto_setup_fabric'])) {
                    $producer->disableAutoSetupFabric();
                }

                $producers[$name] = $producer;
            }

            return $producers;
        });
    }

    private function loadConsumers($app)
    {
        $app['rabbit.consumer'] = $app->share(function ($app) {
            if (!isset($app['rabbit.consumers'])) {
                return;
            }

            $consumers = [];
            foreach ($app['rabbit.consumers'] as $name => $options) {
                $connection = $this->getConnection($app, $options, $app['rabbit.connections']);
                $consumer = new Consumer($connection);
                $consumer->setExchangeOptions($options['exchange_options']);
                $consumer->setQueueOptions($options['queue_options']);
                $consumer->setCallback(array($app[$options['callback']], 'execute'));

                if (array_key_exists('qos_options', $options)) {
                    $consumer->setQosOptions(
                        $options['qos_options']['prefetch_size'],
                        $options['qos_options']['prefetch_count'],
                        $options['qos_options']['global']
                    );
                }

                if (array_key_exists('qos_options', $options)) {
                    $consumer->setIdleTimeout($options['idle_timeout']);
                }

                if ((array_key_exists('auto_setup_fabric', $options)) && (!$options['auto_setup_fabric'])) {
                    $consumer->disableAutoSetupFabric();
                }

                $consumers[$name] = $consumer;
            }

            return $consumers;
        });
    }

    private function loadAnonymousConsumers($app)
    {
        $app['rabbit.anonymous_consumer'] = $app->share(function ($app) {
            if (!isset($app['rabbit.anon_consumers'])) {
                return;
            }

            $consumers = [];
            foreach ($app['rabbit.anon_consumers'] as $name => $options) {
                $connection = $this->getConnection($app, $options, $app['rabbit.connections']);
                $consumer = new AnonConsumer($connection);
                $consumer->setExchangeOptions($options['exchange_options']);
                $consumer->setCallback(array($options['callback'], 'execute'));

                $consumers[$name] = $consumer;
            }

            return $consumers;
        });
    }

    private function loadMultipleConsumers($app)
    {
        $app['rabbit.multiple_consumer'] = $app->share(function ($app) {
            if (!isset($app['rabbit.multiple_consumers'])) {
                return;
            }

            $consumers = [];
            foreach ($app['rabbit.multiple_consumers'] as $name => $options) {
                $connection = $this->getConnection($app, $options, $app['rabbit.connections']);
                $consumer = new MultipleConsumer($connection);
                $consumer->setExchangeOptions($options['exchange_options']);
                $consumer->setQueues($options['queues']);

                if (array_key_exists('qos_options', $options)) {
                    $consumer->setQosOptions(
                        $options['qos_options']['prefetch_size'],
                        $options['qos_options']['prefetch_count'],
                        $options['qos_options']['global']
                    );
                }

                if (array_key_exists('qos_options', $options)) {
                    $consumer->setIdleTimeout($options['idle_timeout']);
                }

                if ((array_key_exists('auto_setup_fabric', $options)) && (!$options['auto_setup_fabric'])) {
                    $consumer->disableAutoSetupFabric();
                }

                $consumers[$name] = $consumer;
            }

            return $consumers;
        });
        
    }

    private function loadRpcClients($app)
    {
        $app['rabbit.rpc_client'] = $app->share(function ($app) {
            if (!isset($app['rabbit.rpc_clients'])) {
                return;
            }

            $clients = [];
            foreach ($app['rabbit.rpc_clients'] as $name => $options) {
                $connection = $this->getConnection($app, $options, $app['rabbit.connections']);
                $client = new RpcClient($connection);

                if (array_key_exists('expect_serialized_response', $options)) {
                    $client->initClient($options['expect_serialized_response']);
                }

                $clients[$name] = $client;
            }

            return $clients;
        });
    }

    private function loadRpcServers($app)
    {
        $app['rabbit.rpc_server'] = $app->share(function ($app) {
            if (!isset($app['rabbit.rpc_servers'])) {
                return;
            }

            $servers = [];
            foreach ($app['rabbit.rpc_servers'] as $name => $options) {
                $connection = $this->getConnection($app, $options, $app['rabbit.connections']);
                $server = new RpcServer($connection);
                $server->initServer($name);
                $server->setCallback(array($options['callback'], 'execute'));

                if (array_key_exists('qos_options', $options)) {
                    $server->setQosOptions(
                        $options['qos_options']['prefetch_size'],
                        $options['qos_options']['prefetch_count'],
                        $options['qos_options']['global']
                    );
                }

                $servers[$name] = $server;
            }

            return $servers;
        });
        
    }
}
