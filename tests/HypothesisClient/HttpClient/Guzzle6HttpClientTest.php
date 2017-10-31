<?php

namespace tests\eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\Exception\ApiException;
use eLife\HypothesisClient\Exception\ApiTimeout;
use eLife\HypothesisClient\Exception\BadResponse;
use eLife\HypothesisClient\Exception\NetworkProblem;
use eLife\HypothesisClient\HttpClient\Guzzle6HttpClient;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\HypothesisClient\Result\HttpResult;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase;
use Traversable;
use function GuzzleHttp\default_user_agent;

/**
 * @covers \eLife\HypothesisClient\HttpClient\Guzzle6HttpClient
 */
final class Guzzle6HttpClientTest extends PHPUnit_Framework_TestCase
{
    private $mock;
    private $history;
    private $stack;
    private $guzzle;

    /**
     * @before
     */
    protected function setUpOriginalClient()
    {
        $this->mock = new MockHandler();
        $this->history = [];

        $this->stack = HandlerStack::create($this->mock);
        $this->stack->push(Middleware::history($this->history));

        $this->guzzle = new Client(['handler' => $this->stack]);
    }

    /**
     * @test
     */
    public function it_is_a_http_client()
    {
        $this->assertInstanceOf(HttpClientInterface::class, new Guzzle6HttpClient($this->guzzle));
    }

    /**
     * @test
     */
    public function it_sends_requests()
    {
        $request = new Request('GET', 'foo');
        $response = new Response(200, [],
            json_encode(['foo' => ['bar', 'baz']]));

        $this->mock->append($response);
        $client = new Guzzle6HttpClient($this->guzzle);

        $this->assertInstanceOf(PromiseInterface::class, $client->send($request));
    }

    /**
     * @test
     */
    public function it_returns_results()
    {
        $request = new Request('GET', 'foo');
        $response = new Response(200, [], json_encode(['foo' => ['bar', 'baz']]));
        $result = HttpResult::fromResponse($response);

        $this->mock->append($response);

        $client = new Guzzle6HttpClient($this->guzzle);

        $this->assertEquals($result, $client->send($request)->wait());
    }

    /**
     * @test
     * @dataProvider userAgentProvider
     */
    public function it_sets_a_user_agent(string $existing = null, string $expected)
    {
        $request = new Request('GET', 'foo', ['User-Agent' => $existing]);
        $response = new Response(200, [], json_encode(['foo' => ['bar', 'baz']]));

        $this->mock->append($response);

        $client = new Guzzle6HttpClient($this->guzzle);

        $client->send($request)->wait();

        $this->assertSame($expected, $this->history[0]['request']->getHeaderLine('User-Agent'));
    }

    public function userAgentProvider() : Traversable
    {
        yield 'sets when empty' => [null, default_user_agent()];
        yield 'appends to existing' => ['bar', sprintf('bar %s', default_user_agent())];
    }

    /**
     * @test
     */
    public function it_throws_response_exceptions_on_broken_api_problems()
    {
        $request = new Request('GET', 'foo');
        $response = new Response(404, [], 'foo bar baz');

        $this->mock->append($response);

        $client = new Guzzle6HttpClient($this->guzzle);

        $this->expectException(BadResponse::class);

        $client->send($request)->wait();
    }

    /**
     * @test
     */
    public function it_throws_api_timeout_exceptions()
    {
        $request = new Request('GET', 'foo');
        $this->mock->append(new ConnectException('Problem', $request, null, ['errno' => 28, 'error' => 'Timeout']));

        $client = new Guzzle6HttpClient($this->guzzle);

        $this->expectException(ApiTimeout::class);

        $client->send($request)->wait();
    }

    /**
     * @test
     */
    public function it_throws_network_exceptions()
    {
        $request = new Request('GET', 'foo');
        $this->mock->append(new RequestException('Problem', $request));

        $client = new Guzzle6HttpClient($this->guzzle);

        $this->expectException(NetworkProblem::class);

        $client->send($request)->wait();
    }

    /**
     * @test
     */
    public function it_throws_api_exceptions()
    {
        $request = new Request('GET', 'foo');
        $this->mock->append(new TransferException());

        $client = new Guzzle6HttpClient($this->guzzle);

        $this->expectException(ApiException::class);

        $client->send($request)->wait();
    }

    /**
     * @test
     */
    public function it_throws_api_exceptions_on_non_throwables()
    {
        $request = new Request('GET', 'foo');
        $this->mock->append(new RejectedPromise('error'));

        $client = new Guzzle6HttpClient($this->guzzle);

        $this->expectException(ApiException::class);

        $client->send($request)->wait();
    }
}
