<?php

namespace tests\eLife\Annotations\Provider;

use Aws\Sqs\SqsClient;
use eLife\Annotations\Provider\AnnotationsServiceProvider;
use eLife\ApiClient\HttpClient;
use eLife\ApiClient\Result\HttpResult;
use eLife\ApiSdk\ApiSdk;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\HypothesisClient\ApiSdk as HypothesisApiSdk;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\Logging\Monitoring;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Knp\Console\Application as ConsoleApplication;
use Knp\Provider\ConsoleServiceProvider;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Silex\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \eLife\Annotations\Provider\AnnotationsServiceProvider
 */
final class AnnotationsServiceProviderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    private $app;
    private $container = [];
    private $httpClient;
    private $sqs;
    private $queue;
    private $logger;

    /**
     * @before
     */
    public function prepareContainers()
    {
        $this->app = new Application();
        $this->httpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['send'])
            ->getMock();
        $this->sqs = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQueueUrl', 'createQueue'])
            ->getMock();
        $this->queue = $this->getMockBuilder(WatchableQueue::class)
            ->setMethods(['enqueue', 'dequeue', 'commit', 'release', 'clean', 'getName', 'count'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'])
            ->getMock();
        $this->container = [
            'annotations.api.sdk' => new ApiSdk($this->httpClient),
            'annotations.hypothesis.sdk' => new HypothesisApiSdk($this->createMock(HttpClientInterface::class)),
            'annotations.limit.interactive' => $this->app->protect($this->createMock(Limit::class)),
            'annotations.limit.long_running' => $this->app->protect($this->createMock(Limit::class)),
            'annotations.logger' => $this->logger,
            'annotations.monitoring' => new Monitoring(),
            'annotations.sqs' => $this->sqs,
            'annotations.sqs.queue' => $this->queue,
            'annotations.sqs.queue_transformer' => $this->createMock(QueueItemTransformer::class),
        ];
    }

    /**
     * @test
     */
    public function commands_are_registered()
    {
        $this->app->register(new ConsoleServiceProvider());
        $this->app->register(new AnnotationsServiceProvider(), $this->container);
        /** @var ConsoleApplication $console */
        $console = $this->app['console'];
        $this->assertTrue($console->has('queue:count'));
        $this->assertTrue($console->has('queue:clean'));
        $this->assertTrue($console->has('queue:create'));
        $this->assertTrue($console->has('queue:import'));
        $this->assertTrue($console->has('queue:push'));
        $this->assertTrue($console->has('queue:watch'));
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage You must register the ConsoleServiceProvider to use the AnnotationsServiceProvider.
     */
    public function registration_fails_if_no_console_provider()
    {
        $this->app->register(new AnnotationsServiceProvider(), $this->container);
    }

    /**
     * @test
     */
    public function queue_import_command_with_invalid_entity_argument()
    {
        $json = [
            'total' => 0,
            'items' => [],
        ];
        $this->httpClient
            ->method('send')
            ->willReturn(new FulfilledPromise(HttpResult::fromResponse(new Response(200, ['Content-Type' => 'application/vnd.elife.profile-list+json; version=1'],
                json_encode($json)))));
        $this->app->register(new ConsoleServiceProvider());
        $this->app->register(new AnnotationsServiceProvider(), $this->container);
        /** @var ConsoleApplication $console */
        $console = $this->app['console'];

        $this->assertTrue($console->has('queue:import'));

        $tester = new CommandTester($command = $console->find('queue:import'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity with name "invalid" not supported.');
        $tester->execute([
            'command' => 'queue:import',
            'entity' => 'invalid',
        ]);
    }

    /**
     * @test
     */
    public function queue_import_command_with_valid_entity_argument()
    {
        $json = [
            'total' => 0,
            'items' => [],
        ];
        $this->httpClient
            ->method('send')
            ->willReturn(new FulfilledPromise(HttpResult::fromResponse(new Response(200, ['Content-Type' => 'application/vnd.elife.profile-list+json; version=1'],
                json_encode($json)))));
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('All entities queued.');
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Importing Profiles.');
        $this->app->register(new ConsoleServiceProvider());
        $this->app->register(new AnnotationsServiceProvider(), $this->container);
        /** @var ConsoleApplication $console */
        $console = $this->app['console'];

        $this->assertTrue($console->has('queue:import'));

        $tester = new CommandTester($command = $console->find('queue:import'));

        $tester->execute([
            'command' => 'queue:import',
            'entity' => 'all',
        ]);
        $output = $tester->getDisplay();
        $this->assertContains('[OK] All entities queued.', $output);
        $tester->execute([
            'command' => 'queue:import',
            'entity' => 'profiles',
        ]);
        $output = $tester->getDisplay();
        $this->assertContains('[OK] All entities queued.', $output);
    }

    /**
     * @test
     */
    public function queue_import_command_with_no_queue_name()
    {
        $json = [
            'total' => 0,
            'items' => [],
        ];
        for ($i = 1; $i <= 5; ++$i) {
            $json['items'][] = [
                'id' => 'id'.$i,
                'name' => [
                    'preferred' => 'Preferred name'.$i,
                    'index' => 'Index name'.$i,
                ],
            ];
            ++$json['total'];
        }
        $this->httpClient
            ->method('send')
            ->willReturn(new FulfilledPromise(HttpResult::fromResponse(new Response(200, ['Content-Type' => 'application/vnd.elife.profile-list+json; version=1'],
                json_encode($json)))));
        $this->queue
            ->expects($this->exactly(5))
            ->method('enqueue');
        $this->app->register(new ConsoleServiceProvider());
        $this->app->register(new AnnotationsServiceProvider(), $this->container);
        /** @var ConsoleApplication $console */
        $console = $this->app['console'];

        $this->assertTrue($console->has('queue:import'));

        $tester = new CommandTester($command = $console->find('queue:import'));

        $tester->execute([
            'command' => 'queue:import',
        ]);
        $output = $tester->getDisplay();
        $this->assertContains('[OK] All entities queued.', $output);
    }
}
