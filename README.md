# RabbitMqBundle #

## About ##

This Silex service provider incorporates the awesome [RabbitMqBundle](http://github.com/videlalvaro/RabbitMqBundle) into your Silex Application. Installing this bundle created by [Alvaro Videla](https://twitter.com/old_sound) you can use [RabbitMQ](http://www.rabbitmq.com/) messaging features in your application, using the [php-amqplib](http://github.com/videlalvaro/php-amqplib) library.

To learn what you can do with the bundle, please read the [README](https://github.com/videlalvaro/RabbitMqBundle/blob/master/README.md).

## Installation ##

Require the library in your composer.json file:

````
{
    "require": {
        "fiunchinho/rabbitmq-service-provider": "1.0.0",
    }
}
```

Tell Composer to fetch the library:

```
$ composer update fiunchinho/rabbitmq-service-provider
```

Then, to activate the service, register the service provider after creating your Silex Application:

```php

use Silex\Application;
use fiunchinho\Silex\Provider\RabbitServiceProvider;

$app = new Application();
$app->register(new RabbitServiceProvider());
```

Start sending messages ;)

## Usage ##

In the [README](https://github.com/videlalvaro/RabbitMqBundle/blob/master/README.md) file from the Symfony bundle you can see all the available options. For example, to configure our service with two different connections and a couple of producers, and one consumer, we will pass the following configuration:

```php
$app->register(new RabbitServiceProvider(), [
    'rabbit.connections' => [
        'default' => [
            'host'      => 'localhost',
            'port'      => 5672,
            'user'      => 'guest',
            'password'  => 'guest',
            'vhost'     => '/'
        ],
        'another' => [
            'host'      => 'another_host',
            'port'      => 5672,
            'user'      => 'guest',
            'password'  => 'guest',
            'vhost'     => '/'
        ]
    ],
    'rabbit.producers' => [
        'first_producer' => [
            'connection'        => 'another',
            'exchange_options'  => ['name' => 'a_exchange', 'type' => 'topic']
        ],
        'second_producer' => [
            'connection'        => 'default',
            'exchange_options'  => ['name' => 'a_exchange', 'type' => 'topic']
        ],
    ],
    'rabbit.consumers' => [
        'a_consumer' => [
            'connection'        => 'default',
            'exchange_options'  => ['name' => 'a_exchange','type' => 'topic'],
            'queue_options'     => ['name' => 'a_queue', 'routing_keys' => ['foo.#']],
            'callback'          => 'your_consumer_service'
        ]
    ]
]);
```

Keep in mind that the callback that you choose in the consumer needs to be a service that has been registered in the Pimple container. Consumer services implement the ConsumerInterface, which has a execute() public method.

## Credits ##

- [RabbitMqBundle](http://github.com/videlalvaro/RabbitMqBundle) bundle by [Alvaro Videla](https://twitter.com/old_sound)