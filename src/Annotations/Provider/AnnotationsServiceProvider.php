<?php

namespace eLife\Annotations\Provider;

use eLife\Annotations\Command\QueueCreateCommand;
use eLife\Annotations\Command\QueueImportCommand;
use eLife\Annotations\Command\QueuePushCommand;
use eLife\Annotations\Command\QueueWatchCommand;
use eLife\Bus\Command\QueueCleanCommand;
use eLife\Bus\Command\QueueCountCommand;
use Knp\Console\Application;
use LogicException;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use ReflectionClass;

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
            throw new LogicException(sprintf('You must register the ConsoleServiceProvider to use the %s.', (new ReflectionClass(self::class))->getShortName()));
        }

        $container->extend('console', function (Application $console) use ($container) {
            $console->add(new QueueCleanCommand($container['annotations.sqs.queue'], $container['annotations.logger']));
            $console->add(new QueueCountCommand($container['annotations.sqs.queue']));
            $console->add(new QueuePushCommand($container['annotations.sqs.queue'], $container['annotations.logger'], $container['annotations.sqs.queue_message_type'] ?? null));
            $console->add(new QueueCreateCommand($container['annotations.sqs'], $container['annotations.logger'], $container['annotations.sqs.queue_name'] ?? null, $container['annotations.sqs.region'] ?? null));
            $console->add(new QueueImportCommand(
                $container['annotations.api.sdk'],
                $container['annotations.sqs.queue'],
                $container['annotations.logger'],
                $container['annotations.monitoring'],
                $container['annotations.limit.import']
            ));
            $console->add(new QueueWatchCommand(
                $container['annotations.sqs.queue'],
                $container['annotations.sqs.queue_transformer'],
                $container['annotations.hypothesis.sdk'],
                $container['annotations.logger'],
                $container['annotations.monitoring'],
                $container['annotations.limit.watch']
            ));

            return $console;
        });
    }
}
