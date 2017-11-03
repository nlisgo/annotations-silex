<?php

namespace eLife\Annotations\Provider;

use eLife\Bus\Command\QueueCleanCommand;
use eLife\Bus\Command\QueueCountCommand;
use Knp\Console\Application;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class AnnotationsServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the annotations service console commands.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        if (!isset($container['console'])) {
            throw new \LogicException('You must register the ConsoleServiceProvider to use the '.self::class.'.');
        }

        $container->extend('console', function (Application $console) use ($container) {
            $console->add(new QueueCleanCommand($container['annotations.queue'], $container['annotations.logger']));
            $console->add(new QueueCountCommand($container['annotations.queue']));

            return $console;
        });
    }
}
