<?php

namespace tests\eLife\Annotations\Command;

use eLife\Annotations\Command\QueueImportCommand;
use eLife\Annotations\Command\QueuePushCommand;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\WatchableQueue;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \eLife\Annotations\Command\QueuePushCommand
 */
class QueuePushCommandTest extends PHPUnit_Framework_TestCase
{
    /** @var Application */
    private $application;
    /** @var QueueImportCommand */
    private $command;
    /** @var CommandTester */
    private $commandTester;
    private $logger;
    private $queue;

    /**
     * @before
     */
    public function prepareDependencies()
    {
        $this->application = new Application();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'])
            ->getMock();
        $this->queue = $this->getMockBuilder(WatchableQueue::class)
            ->setMethods(['enqueue', 'dequeue', 'commit', 'release', 'clean', 'getName', 'count'])
            ->getMock();
    }

    /**
     * @test
     */
    public function it_will_push_to_the_queue()
    {
        $this->prepareCommandTester();
        $this->queue
            ->expects($this->exactly(1))
            ->method('enqueue')
            ->with(new InternalSqsMessage('profiles', 'id'));
        $this->commandTesterExecute('id', 'profiles');
    }

    /**
     * @test
     */
    public function it_requires_an_id()
    {
        $this->prepareCommandTester();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "id").');
        $this->commandTesterExecute(null, 'profiles');
    }

    /**
     * @test
     */
    public function it_requires_a_type()
    {
        $this->prepareCommandTester();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "type").');
        $this->commandTesterExecute('id', null);
    }

    /**
     * @test
     */
    public function it_may_have_a_default_type()
    {
        $this->prepareCommandTester('defaultType');
        $this->queue
            ->expects($this->exactly(1))
            ->method('enqueue')
            ->with(new InternalSqsMessage('defaultType', 'id'));
        $this->commandTesterExecute('id', null);
    }

    /**
     * @test
     */
    public function it_may_override_the_default_type()
    {
        $this->prepareCommandTester('defaultType');
        $this->queue
            ->expects($this->exactly(1))
            ->method('enqueue')
            ->with(new InternalSqsMessage('overrideType', 'id'));
        $this->commandTesterExecute('id', 'overrideType');
    }

    private function prepareCommandTester($type = null)
    {
        $this->command = new QueuePushCommand($this->queue, $this->logger, $type);
        $this->application->add($this->command);
        $this->commandTester = new CommandTester($command = $this->application->get($this->command->getName()));
    }

    private function commandTesterExecute($id, $type)
    {
        $execArgs = array_filter(
            [
                'command' => $this->command->getName(),
                'id' => $id,
                'type' => $type,
            ]
        );
        $this->commandTester->execute($execArgs);
    }
}
