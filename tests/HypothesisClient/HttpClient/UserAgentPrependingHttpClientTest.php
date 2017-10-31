<?php

namespace tests\eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\HypothesisClient\HttpClient\NotifyingHttpClient;
use eLife\HypothesisClient\HttpClient\UserAgentPrependingHttpClient;
use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Traversable;

/**
 * @covers \eLife\HypothesisClient\HttpClient\UserAgentPrependingHttpClient
 */
final class UserAgentPrependingHttpClientTest extends PHPUnit_Framework_TestCase
{
    private $originalClient;
    private $requests;

    /**
     * @before
     */
    protected function setUpOriginalClient()
    {
        $this->requests = [];

        $this->originalClient = new NotifyingHttpClient($this->createMock(HttpClientInterface::class));

        $this->originalClient->addRequestListener(function (RequestInterface $request) {
            $this->requests[] = $request;
        });
    }

    /**
     * @test
     * @dataProvider userAgentProvider
     */
    public function it_sets_a_user_agent(string $existing = null, string $input, string $expected)
    {
        $request = new Request('GET', 'foo', ['User-Agent' => $existing]);

        $client = new UserAgentPrependingHttpClient($this->originalClient, $input);

        $client->send($request)->wait();

        $this->assertSame($expected, $this->requests[0]->getHeaderLine('User-Agent'));
    }

    public function userAgentProvider() : Traversable
    {
        yield 'sets when empty' => [null, 'foo', 'foo'];
        yield 'prepends to existing' => ['bar', 'foo', 'foo bar'];
    }
}
