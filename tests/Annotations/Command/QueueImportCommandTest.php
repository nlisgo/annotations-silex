<?php

namespace tests\eLife\Annotations\Command;

use eLife\Annotations\Command\QueueImportCommand;
use eLife\ApiClient\HttpClient;
use eLife\ApiClient\Result\HttpResult;
use eLife\ApiSdk\ApiSdk;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Logging\Monitoring;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \eLife\Annotations\Command\QueueImportCommand
 */
class QueueImportCommandTest extends PHPUnit_Framework_TestCase
{
    /** @var ApiSdk */
    private $apiSdk;
    /** @var Application */
    private $application;
    /** @var QueueImportCommand */
    private $command;
    /** @var CommandTester */
    private $commandTester;
    private $httpClient;
    private $limit;
    private $logger;
    /** @var Monitoring */
    private $monitoring;
    private $queue;

    /**
     * @before
     */
    public function prepareDependencies()
    {
        $this->httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['send'])
            ->getMock();
        $this->apiSdk = new ApiSdk($this->httpClient);
        $this->application = new Application();
        $this->limit = $this->createMock(Limit::class);
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'])
            ->getMock();
        $this->monitoring = new Monitoring();
        $this->queue = $this->getMockBuilder(WatchableQueue::class)
            ->setMethods(['enqueue', 'dequeue', 'commit', 'release', 'clean', 'getName', 'count'])
            ->getMock();
        $this->command = new QueueImportCommand($this->apiSdk, $this->queue, $this->logger, $this->monitoring, $this->limit);
        $this->application->add($this->command);
        $this->commandTester = new CommandTester($command = $this->application->get($this->command->getName()));
    }

    /**
     * @test
     */
    public function it_will_import_items_to_the_queue()
    {
        $this->httpClient
            ->method('send')
            ->willReturn($this->prepareMockResponse(5));
        $this->queue
            ->expects($this->exactly(5))
            ->method('enqueue');
        $this->commandTesterExecute('all');
        $this->assertStringEndsWith('[OK] All entities queued.', trim($this->commandTester->getDisplay()));
    }

    /**
     * @test
     */
    public function it_will_display_a_progress_bar()
    {
        $this->httpClient
            ->method('send')
            ->willReturn($this->prepareMockResponse(1));
        $this->queue
            ->expects($this->exactly(1))
            ->method('enqueue');
        $this->commandTesterExecute('all');
        $display = trim($this->commandTester->getDisplay());
        $this->assertStringStartsWith('1/1 [============================] 100%', $display);
        $this->assertStringEndsWith('[OK] All entities queued.', $display);
    }

    /**
     * @test
     */
    public function it_will_update_the_log()
    {
        $this->httpClient
            ->method('send')
            ->willReturn($this->prepareMockResponse(1));
        $this->logger
            ->expects($this->exactly(5))
            ->method('info');
        $this->logger
            ->expects($this->at(0))
            ->method('info')
            ->with('Importing Profiles.');
        $this->logger
            ->expects($this->at(1))
            ->method('info')
            ->with('Importing 1 item(s) of type "profile".');
        $this->logger
            ->expects($this->at(2))
            ->method('info')
            ->with('Item (profile, id0) being enqueued.');
        $this->logger
            ->expects($this->at(3))
            ->method('info')
            ->with('Item (profile, id0) enqueued successfully.');
        $this->logger
            ->expects($this->at(4))
            ->method('info')
            ->with('All entities queued.');
        $this->commandTesterExecute('all');
    }

    /**
     * @test
     */
    public function if_entity_is_passed_it_must_be_valid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity with name "invalid" not supported.');
        $this->commandTesterExecute('invalid');
    }

    private function commandTesterExecute($entity = null)
    {
        $execArgs = array_filter(
            [
                'command' => $this->command->getName(),
                'entity' => $entity,
            ]
        );
        $this->commandTester->execute($execArgs);
    }

    private function prepareMockResponse($count = 0) : FulfilledPromise
    {
        $items = [];
        if ($count > 0) {
            for ($i = 0; $i < $count; ++$i) {
                $items[] = [
                    'id' => 'id'.$i,
                    'name' => [
                        'preferred' => 'Preferred name'.$i,
                        'index' => 'Index name'.$i,
                    ],
                ];
            }
        }
        $json = [
            'total' => $count,
            'items' => $items,
        ];

        return new FulfilledPromise(HttpResult::fromResponse(new Response(200, ['Content-Type' => 'application/vnd.elife.profile-list+json; version=1'], json_encode($json))));
    }
}
