<?php

namespace tests\eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\HttpClientInterface;
use eLife\HypothesisClient\HttpClient\NotifyingHttpClientInterface;
use eLife\HypothesisClient\HttpClient\UserAgentPrependingHttpClientInterface;
use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Traversable;

/**
 * @covers \eLife\HypothesisClient\HttpClient\UserAgentPrependingHttpClientInterface
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

        $this->originalClient = new NotifyingHttpClientInterface($this->createMock(HttpClientInterface::class));

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

        $client = new UserAgentPrependingHttpClientInterface($this->originalClient, $input);

        $client->send($request)->wait();

        $this->assertSame($expected, $this->requests[0]->getHeaderLine('User-Agent'));
    }

    public function userAgentProvider() : Traversable
    {
        yield 'sets when empty' => [null, 'foo', 'foo'];
        yield 'prepends to existing' => ['bar', 'foo', 'foo bar'];
    }
}
