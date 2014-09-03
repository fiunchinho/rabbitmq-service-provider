<?php

namespace fiunchinho\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\AnonConsumer;
use OldSound\RabbitMqBundle\RabbitMq\MultipleConsumer;
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
    private function getConnectionName($options, $connections)
    {
    	$connection_name = @$options['connection']?: self::DEFAULT_CONNECTION;

		if (!isset($connections[$connection_name])) {
			throw new \InvalidArgumentException('Configuration for connection [' . $connection_name . '] not found');
		}

		return 'rabbit.connection.' . $connection_name;
    }

    private function loadConnections($app)
    {
    	if (!isset($app['rabbit.connections'])) {
    		throw new \InvalidArgumentException('You need to specify at least a connection in your configuration.');
    	}

    	foreach ($app['rabbit.connections'] as $name => $options) {
			$connection = new AMQPLazyConnection(
	        	$app['rabbit.connections'][$name]['host'],
	        	$app['rabbit.connections'][$name]['port'],
	        	$app['rabbit.connections'][$name]['user'],
	        	$app['rabbit.connections'][$name]['password'],
	        	$app['rabbit.connections'][$name]['vhost']
	        );

			$app['rabbit.connection.' . $name] = $app->share(function ($app) use ($connection) {
				return $connection;
			});
		}
    }

    private function loadProducers($app)
    {
    	if (!isset($app['rabbit.producers'])) {
    		return;
    	}

    	foreach ($app['rabbit.producers'] as $name => $options) {
			$connection_name = $this->getConnectionName($options, $app['rabbit.connections']);

            $producer = new Producer($app[$connection_name]);
			$producer->setExchangeOptions($options['exchange_options']);

			if ((array_key_exists('auto_setup_fabric', $options)) && (!$options['auto_setup_fabric'])) {
				$producer->disableAutoSetupFabric();
			}

			$app['rabbit.producer.' . $name] = $app->share(function ($app) use ($producer) {
				return $producer;
			});
		}
    }

    private function loadConsumers($app)
    {
    	if (!isset($app['rabbit.consumers'])) {
    		return;
    	}

    	foreach ($app['rabbit.consumers'] as $name => $options) {
			$connection_name = $this->getConnectionName($options, $app['rabbit.connections']);
            $consumer = new Consumer($app[$connection_name]);
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

			$app['rabbit.consumer.' . $name] = $app->share(function ($app) use ($consumer) {
				return $consumer;
			});
		}
	}

	private function loadAnonymousConsumers($app)
	{
		if (!isset($app['rabbit.anon_consumers'])) {
    		return;
    	}

		foreach ($app['rabbit.anon_consumers'] as $name => $options) {
			$connection_name = $this->getConnectionName($options, $app['rabbit.connections']);
            $consumer = new AnonConsumer($app[$connection_name]);
			$consumer->setExchangeOptions($options['exchange_options']);
			$consumer->setCallback(array($options['callback'], 'execute'));

			$app['rabbit.anonymous.' . $name] = $app->share(function ($app) use ($consumer) {
				return $consumer;
			});
		}
	}

	private function loadMultipleConsumers($app)
	{
		if (!isset($app['rabbit.multiple_consumers'])) {
    		return;
    	}

    	foreach ($app['rabbit.multiple_consumers'] as $name => $options) {
			$connection_name = $this->getConnectionName($options, $app['rabbit.connections']);
            $consumer = new MultipleConsumer($app[$connection_name]);
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

			$app['rabbit.multiple.' . $name] = $app->share(function ($app) use ($consumer) {
				return $consumer;
			});
		}
    }

    private function loadRpcClients($app)
    {
		if (!isset($app['rabbit.rpc_clients'])) {
    		return;
    	}

    	foreach ($app['rabbit.rpc_clients'] as $name => $options) {
			$connection_name = $this->getConnectionName($options, $app['rabbit.connections']);
            $client = new RpcClient($app[$connection_name]);

			if (array_key_exists('expect_serialized_response', $options)) {
				$client->initClient($options['expect_serialized_response']);
			}

			$app['rabbit.rpc_client.' . $name] = $app->share(function ($app) use ($client) {
				return $client;
			});
		}
    }

    private function loadRpcServers($app)
    {
		if (!isset($app['rabbit.rpc_servers'])) {
    		return;
    	}

    	foreach ($app['rabbit.rpc_servers'] as $name => $options) {
			$connection_name = $this->getConnectionName($options, $app['rabbit.connections']);
            $server = new RpcServer($app[$connection_name]);
            $server->initServer($name);
            $server->setCallback(array($options['callback'], 'execute'));

			if (array_key_exists('qos_options', $options)) {
				$server->setQosOptions(
					$options['qos_options']['prefetch_size'],
					$options['qos_options']['prefetch_count'],
					$options['qos_options']['global']
				);
			}

			$app['rabbit.rpc_client.' . $name] = $app->share(function ($app) use ($server) {
				return $server;
			});
		}
    }
}
