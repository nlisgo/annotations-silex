<?php

namespace tests\eLife\Annotations\Provider;

use Aws\Sqs\SqsClient;
use eLife\Annotations\Provider\AnnotationsServiceProvider;
use eLife\ApiClient\HttpClient;
use eLife\ApiSdk\ApiSdk;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\Mock\WatchableQueueMock;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\HypothesisClient\ApiSdk as HypothesisApiSdk;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\Logging\Monitoring;
use Knp\Provider\ConsoleServiceProvider;
use LogicException;
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

    /**
     * @before
     */
    public function prepareContainers()
    {
        $this->app = new Application();
        $this->container = [
            'annotations.api.sdk' => new ApiSdk($this->createMock(HttpClient::class)),
            'annotations.hypothesis.sdk' => new HypothesisApiSdk($this->createMock(HttpClientInterface::class)),
            'annotations.limit.interactive' => $this->app->protect($this->createMock(Limit::class)),
            'annotations.limit.long_running' => $this->app->protect($this->createMock(Limit::class)),
            'annotations.logger' => $this->createMock(LoggerInterface::class),
            'annotations.monitoring' => new Monitoring(),
            'annotations.sqs' => $this->createMock(SqsClient::class),
            'annotations.sqs.queue' => new WatchableQueueMock(),
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
        /** @var \Knp\Console\Application $console */
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
     * @expectedException LogicException
     * @expectedExceptionMessage You must register the ConsoleServiceProvider to use the AnnotationsServiceProvider.
     */
    public function registration_fails_if_no_console_provider()
    {
        $this->app->register(new AnnotationsServiceProvider(), $this->container);
    }
}
