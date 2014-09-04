# RabbitMqBundle #

## About ##

This Silex service provider incorporates the awesome [RabbitMqBundle](http://github.com/videlalvaro/RabbitMqBundle) into your Silex Application. Installing this bundle created by [php-amqplib](http://github.com/videlalvaro/php-amqplib) you can use [RabbitMQ](http://www.rabbitmq.com/) messaging features in your application, using the [php-amqplib](http://github.com/videlalvaro/php-amqplib) library.

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

Keep in mind that your callbacks _need to be registered_ as normal Symfony2 services. There you can inject the service container, the database service, the Symfony logger, and so on.

## Usage ##

In the [README](https://github.com/videlalvaro/RabbitMqBundle/blob/master/README.md) file from the Symfony bundle you can see all the available options. For example, to configure our service with two different connections and a couple of producers, we will pass the following configuration:

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
    ]
]);
```

Here we configure the connection service and the message endpoints that our application will have. In this example your service container will contain the service `old_sound_rabbit_mq.upload_picture_producer` and `old_sound_rabbit_mq.upload_picture_consumer`. The later expects that there's a service called `upload_picture_service`.

If you don't specify a connection for the client, the client will look for a connection with the same alias. So for our `upload_picture` the service container will look for an `upload_picture` connection.

If you need to add optional queue arguments, then your queue options can be something like this:

```yaml
queue_options: {name: 'upload-picture', arguments: {'x-ha-policy': ['S', 'all']}}
```

another example with message TTL of 20 seconds:

```yaml
queue_options: {name: 'upload-picture', arguments: {'x-message-ttl': ['I', 20000]}}
```

The argument value must be a list of datatype and value. Valid datatypes are:

* `S` - String
* `I` - Integer
* `D` - Decimal
* `T` - Timestamps
* `F` - Table
* `A` - Array

Adapt the `arguments` according to your needs.

If you want to bind queue with specific routing keys you can declare it in producer or consumer config:

```yaml
queue_options:
    name: "upload-picture"
    routing_keys:
      - 'android.#.upload'
      - 'iphone.upload'
```

## Credits ##

- [RabbitMqBundle](http://github.com/videlalvaro/RabbitMqBundle) bundle by [Alvaro Videla](https://twitter.com/old_sound)