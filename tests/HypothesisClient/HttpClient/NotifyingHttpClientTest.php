<?php

namespace tests\eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\HttpClientInterface;
use eLife\HypothesisClient\HttpClient\NotifyingHttpClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use function GuzzleHttp\Promise\promise_for;

/**
 * @covers \eLife\HypothesisClient\HttpClient\NotifyingHttpClientInterface
 */
final class NotifyingHttpClientTest extends PHPUnit_Framework_TestCase
{
    private $originalClient;
    private $client;

    protected function setUp()
    {
        parent::setUp();

        $this->originalClient = $this->createMock(HttpClientInterface::class);
        $this->client = new NotifyingHttpClientInterface($this->originalClient);
    }

    /**
     * @test
     */
    public function it_allows_listeners_to_monitor_requests()
    {
        $request = new Request('GET', 'foo');
        $response = new Response(200);
        $this->originalClient->expects($this->once())
            ->method('send')
            ->with($request)
            ->will($this->returnValue(promise_for($response)));
        $this->sentRequests = [];
        $this->client->addRequestListener(function ($request) {
            $this->sentRequests[] = $request;
        });

        $this->client->send($request);

        $this->assertEquals([$request], $this->sentRequests);
    }

    /**
     * @test
     */
    public function it_does_not_propagate_errors_of_listeners()
    {
        $request = new Request('GET', 'foo');

        $this->client->addRequestListener(function ($request) {
            throw new RuntimeException('mocked error in listener');
        });

        $this->client->send($request);
        $this->assertTrue(true);
    }
}
