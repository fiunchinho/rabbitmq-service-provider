<?php

namespace fiunchinho\Silex\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Knp\Command\Command as BaseCommand;

class Consumer extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('rabbitmq:consumer')
            ->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
            ->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 0)
            ->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'Routing Key', '')
            ->addOption('memory-limit', 'l', InputOption::VALUE_OPTIONAL, 'Allowed memory for this process', null)
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging')
        ;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \InvalidArgumentException When the number of messages to consume is less than 0
     * @throws \BadFunctionCallException When the pcntl is not installed and option -s is true
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (defined('AMQP_DEBUG') === false) {
            define('AMQP_DEBUG', (bool) $input->getOption('debug'));
        }

        $this->amount = $input->getOption('messages');

        if (0 > $this->amount) {
            throw new \InvalidArgumentException("The -m option should be null or greater than 0");
        }

        $this->consumer = $this->getConsumerInstance($input);

        if (!is_null($input->getOption('memory-limit')) && ctype_digit((string)$input->getOption('memory-limit')) && $input->getOption('memory-limit') > 0) {
            $this->consumer->setMemoryLimit($input->getOption('memory-limit'));
        }
        $this->consumer->setRoutingKey($input->getOption('route'));
        $this->consumer->consume($this->amount);
    }

    protected function getConsumerInstance($input)
    {
    	$app = $this->getSilexApplication();
    	return $app['rabbit.consumer'][$input->getArgument('name')];
    }
}