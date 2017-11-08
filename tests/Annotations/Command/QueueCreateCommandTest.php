<?php

namespace tests\eLife\Annotations\Command;

use Aws\Result;
use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use eLife\Annotations\Command\QueueCreateCommand;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \eLife\Annotations\Command\QueueCreateCommand
 */
class QueueCreateCommandTest extends PHPUnit_Framework_TestCase
{
    /** @var Application */
    private $application;
    /** @var QueueCreateCommand */
    private $command;
    /** @var CommandTester */
    private $commandTester;
    private $logger;
    private $sqs;

    /**
     * @before
     */
    public function prepareDependencies()
    {
        $this->application = new Application();
        $this->sqs = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQueueUrl', 'createQueue'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'])
            ->getMock();
    }

    /**
     * @test
     */
    public function it_will_create_a_queue()
    {
        $this->prepareCommandTester();
        $this->mockNewQueueExpectation('newQueue');
        $this->assertEquals('[OK] Queue "newQueue" created successfully.', trim($this->commandTester->getDisplay()));
    }

    /**
     * @test
     */
    public function it_will_update_the_log()
    {
        $this->logger
            ->expects($this->exactly(1))
            ->method('info');
        $this->logger
            ->expects($this->at(0))
            ->method('info')
            ->with('Queue "newQueue" created successfully.');
        $this->prepareCommandTester();
        $this->mockNewQueueExpectation('newQueue');
    }

    /**
     * @test
     */
    public function it_will_display_warning_if_queue_already_exists()
    {
        $this->prepareCommandTester();
        $this->mockExistingQueueExpectation('existingQueue');
        $this->assertEquals('[WARNING] Queue "existingQueue" already exists.', trim($this->commandTester->getDisplay()));
    }

    /**
     * @test
     */
    public function it_requires_a_queue_name()
    {
        $this->prepareCommandTester();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "queueName").');
        $this->commandTesterExecute(null);
    }

    /**
     * @test
     */
    public function it_may_have_a_default_queue_name()
    {
        $this->prepareCommandTester('defaultQueue');
        $this->mockNewQueueExpectation('defaultQueue', null, false);
        $this->assertEquals('[OK] Queue "defaultQueue" created successfully.', trim($this->commandTester->getDisplay()));
    }

    /**
     * @test
     */
    public function it_may_have_a_region_option()
    {
        $this->prepareCommandTester();
        $this->mockNewQueueExpectation('newQueue', 'optionRegion');
        $this->assertEquals('[OK] Queue "newQueue" (optionRegion) created successfully.', trim($this->commandTester->getDisplay()));
    }

    /**
     * @test
     */
    public function it_may_have_a_default_region()
    {
        $this->prepareCommandTester(null, 'defaultRegion');
        $this->mockNewQueueExpectation('newQueue', 'defaultRegion', null, false);
        $this->assertEquals('[OK] Queue "newQueue" (defaultRegion) created successfully.', trim($this->commandTester->getDisplay()));
    }

    private function prepareCommandTester($queueName = null, $region = null)
    {
        $this->command = new QueueCreateCommand($this->sqs, $this->logger, $queueName, $region);
        $this->application->add($this->command);
        $this->commandTester = new CommandTester($command = $this->application->get($this->command->getName()));
    }

    private function mockNewQueueExpectation($sqsQueueName, $sqsRegion = null, $cmdQueueName = null, $cmdRegion = null)
    {
        $sqsArgs = [
            'Region' => $sqsRegion,
            'QueueName' => $sqsQueueName,
        ];
        $this->sqs
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($sqsArgs)
            ->will($this->throwException($this->createMock(SqsException::class)));
        $this->sqs
            ->expects($this->once())
            ->method('createQueue')
            ->with($sqsArgs)
            ->willReturn($this->createMock(Result::class));
        $this->commandTesterExecute((is_null($cmdQueueName) ? $sqsQueueName : $cmdQueueName), (is_null($cmdRegion) ? $sqsRegion : $cmdRegion));
    }

    private function mockExistingQueueExpectation($sqsQueueName, $sqsRegion = null, $cmdQueueName = null, $cmdRegion = null)
    {
        $sqsArgs = [
            'Region' => $sqsRegion,
            'QueueName' => $sqsQueueName,
        ];
        $this->sqs
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($sqsArgs)
            ->willReturn($this->createMock(Result::class));
        $this->commandTesterExecute((is_null($cmdQueueName) ? $sqsQueueName : $cmdQueueName), (is_null($cmdRegion) ? $sqsRegion : $cmdRegion));
    }

    private function commandTesterExecute($queueName, $region = null)
    {
        $execArgs = array_filter(
            [
                'command' => $this->command->getName(),
                'queueName' => $queueName,
                '--region' => $region,
            ]
        );
        $this->commandTester->execute($execArgs);
    }
}
