<?php

namespace tests\eLife\Annotations\Command;

use eLife\Annotations\Command\QueueWatchCommand;
use eLife\Bus\Limit\CallbackLimit;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\BusSqsMessage;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\HypothesisClient\ApiSdk as HypothesisSdk;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\Logging\Monitoring;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \eLife\Annotations\Command\QueueWatchCommand
 */
class QueueWatchCommandTest extends PHPUnit_Framework_TestCase
{
    /** @var Application */
    private $application;
    /** @var QueueWatchCommand */
    private $command;
    /** @var CommandTester */
    private $commandTester;
    /** @var HypothesisSdk */
    private $hypothesisSdk;
    private $limit;
    private $logger;
    /** @var Monitoring */
    private $monitoring;
    private $transformer;
    private $queue;

    /**
     * @before
     */
    public function prepareDependencies()
    {
        $this->application = new Application();
        $this->httpClient = $this->getMockBuilder(HttpClientInterface::class)
            ->setMethods(['send'])
            ->getMock();
        $this->hypothesisSdk = new HypothesisSdk($this->httpClient);
        $this->limit = $this->limitIterations(1);
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'])
            ->getMock();
        $this->monitoring = new Monitoring();
        $this->transformer = $this->getMockBuilder(QueueItemTransformer::class)
            ->setMethods(['transform'])
            ->getMock();
        $this->queue = $this->getMockBuilder(WatchableQueue::class)
            ->setMethods(['enqueue', 'dequeue', 'commit', 'release', 'clean', 'getName', 'count'])
            ->getMock();
    }

    /**
     * @test
     */
    public function it_will_read_an_item_from_on_the_queue()
    {
        $this->prepareCommandTester();
        $this->queue
            ->method('dequeue')
            ->willReturn(new BusSqsMessage('messageId', 'id', 'type', 'receipt'));
        $this->commandTesterExecute();
        $this->assertTrue(true);
    }

    private function prepareCommandTester($serializedTransform = false)
    {
        $this->command = new QueueWatchCommand($this->queue, $this->transformer, $this->hypothesisSdk, $this->logger, $this->monitoring, $this->limit, $serializedTransform);
        $this->application->add($this->command);
        $this->commandTester = new CommandTester($command = $this->application->get($this->command->getName()));
    }

    private function commandTesterExecute()
    {
        $execArgs = array_filter(
            [
                'command' => $this->command->getName(),
            ]
        );
        $this->commandTester->execute($execArgs);
    }

    private function limitIterations(int $number) : Limit
    {
        $iterationCounter = 0;

        return new CallbackLimit(function () use ($number, &$iterationCounter) {
            ++$iterationCounter;
            if ($iterationCounter > $number) {
                return true;
            }

            return false;
        });
    }
}
