<?php

namespace fiunchinho\Silex\Provider;

use Silex\Application;

class RabbitServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testConnectionsAreRegistered()
    {
        $app = new Application();

        $app->register(new RabbitServiceProvider(), [
            'rabbit.connections' => [
                'default' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ],
                'another' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ]
            ]
        ]);

        $this->assertInstanceOf("PhpAmqpLib\Connection\AMQPConnection", $app['rabbit.connection']['default']);
        $this->assertInstanceOf("PhpAmqpLib\Connection\AMQPConnection", $app['rabbit.connection']['another']);
    }

    public function testProducersAreRegistered()
    {
        $app = new Application();

        $app->register(new RabbitServiceProvider(), [
            'rabbit.connections' => [
                'default' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ]
            ],
            'rabbit.producers' => [
                'a_producer' => [
                    'connection'        => 'default',
                    'exchange_options'  => ['name' => 'a_exchange', 'type' => 'topic']
                ],
                'second_producer' => [
                    'connection'        => 'default',
                    'exchange_options'  => ['name' => 'a_exchange', 'type' => 'topic']
                ],
            ]
        ]);

        $this->assertInstanceOf("OldSound\RabbitMqBundle\RabbitMq\Producer", $app['rabbit.producer']['a_producer']);
        $this->assertInstanceOf("OldSound\RabbitMqBundle\RabbitMq\Producer", $app['rabbit.producer']['second_producer']);
    }

    public function testConsumersAreRegistered()
    {
        $app = new Application();

        $app->register(new RabbitServiceProvider(), [
            'rabbit.connections' => [
                'default' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ],
                'another' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ]
            ],
            'rabbit.consumers' => [
                'a_consumer' => [
                    'connection'        => 'default',
                    'exchange_options'  => ['name' => 'a_exchange','type' => 'topic'],
                    'queue_options'     => ['name' => 'a_queue', 'routing_keys' => ['foo.#']],
                    'callback'          => 'debug'
                ],
                'second_consumer' => [
                    'connection'        => 'another',
                    'exchange_options'  => ['name' => 'a_exchange','type' => 'topic'],
                    'queue_options'     => ['name' => 'a_queue', 'routing_keys' => ['#.foo.#']],
                    'callback'          => 'debug'
                ],
            ]
        ]);

        $this->assertInstanceOf("OldSound\RabbitMqBundle\RabbitMq\Consumer", $app['rabbit.consumer']['a_consumer']);
        $this->assertInstanceOf("OldSound\RabbitMqBundle\RabbitMq\Consumer", $app['rabbit.consumer']['second_consumer']);
    }

    public function testAnonymousConsumersAreRegistered()
    {
        $app = new Application();

        $app->register(new RabbitServiceProvider(), [
            'rabbit.connections' => [
                'default' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ],
                'another' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ]
            ],
            'rabbit.anon_consumers' => [
                'anoymous' => [
                    'connection'        => 'another',
                    'exchange_options'  => ['name' => 'exchange_name','type' => 'topic'],
                    'callback'          => 'debug'
                ]
            ]
        ]);

        $this->assertInstanceOf("OldSound\RabbitMqBundle\RabbitMq\Consumer", $app['rabbit.anonymous_consumer']['anoymous']);
    }

    public function testMultiplesConsumersAreRegistered()
    {
       $app = new Application();

        $app->register(new RabbitServiceProvider(), [
            'rabbit.connections' => [
                'default' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ],
                'another' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ]
            ],
            'rabbit.multiple_consumers' => [
                'multiple' => [
                    'connection'        => 'default',
                    'exchange_options'  => ['name' => 'exchange_name','type' => 'topic'],
                    'queues'            => [
                        'exchange_name' => ['name' => 'queue_name', 'routing_keys' => ['foo.#'], 'callback' => 'debug']
                    ]
                ]
            ]
        ]);

        $this->assertInstanceOf("OldSound\RabbitMqBundle\RabbitMq\Consumer", $app['rabbit.multiple_consumer']['multiple']);
    }

    public function testRpcClientsAreRegistered()
    {
        $app = new Application();

        $app->register(new RabbitServiceProvider(), [
            'rabbit.connections' => [
                'another' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ]
            ],
            'rabbit.rpc_clients' => [
                'a_client' => [
                    'connection'                    => 'another',
                    'expect_serialized_response'    => false
                ]
            ]
        ]);

        $this->assertInstanceOf("OldSound\RabbitMqBundle\RabbitMq\RpcClient", $app['rabbit.rpc_client']['a_client']);
    }

    public function testRpcServersAreRegistered()
    {
        $app = new Application();

        $app->register(new RabbitServiceProvider(), [
            'rabbit.connections' => [
                'another' => [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/'
                ]
            ],
            'rabbit.rpc_servers' => [
                'a_server' => [
                    'connection'    => 'another',
                    'callback'      => 'random_int_server',
                    'qos_options'   => ['prefetch_size' => 0, 'prefetch_count' => 1, 'global' => false]
                ]
            ]
        ]);

        $this->assertInstanceOf("OldSound\RabbitMqBundle\RabbitMq\RpcServer", $app['rabbit.rpc_server']['a_server']);
    }
}