<?php

namespace tests\eLife\Annotations\Provider;

use Aws\Sqs\SqsClient;
use eLife\Annotations\Provider\AnnotationsServiceProvider;
use eLife\ApiClient\HttpClient;
use eLife\ApiSdk\ApiSdk;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\HypothesisClient\ApiSdk as HypothesisApiSdk;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\Logging\Monitoring;
use Knp\Console\Application as ConsoleApplication;
use Knp\Provider\ConsoleServiceProvider;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Silex\Application;

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
            'annotations.limit.import' => $this->app->protect($this->createMock(Limit::class)),
            'annotations.limit.watch' => $this->app->protect($this->createMock(Limit::class)),
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
}
