<?php

namespace eLife\HypothesisClient;

use eLife\HypothesisClient\HttpClient\UserAgentPrependingHttpClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

trait ApiClientTrait
{
    private $httpClient;
    private $headers;

    public function __construct(HttpClientInterface $httpClient, array $headers = [])
    {
        $this->httpClient = new UserAgentPrependingHttpClientInterface($httpClient, 'HypothesisClient');
        $this->headers = $headers;
    }

    final protected function deleteRequest(UriInterface $uri, array $headers) : PromiseInterface
    {
        $request = new Request('DELETE', $uri, array_merge($this->headers, $headers));

        return $this->httpClient->send($request);
    }

    final protected function getRequest(UriInterface $uri, array $headers) : PromiseInterface
    {
        $request = new Request('GET', $uri, array_merge($this->headers, $headers));

        return $this->httpClient->send($request);
    }

    final protected function postRequest(
        UriInterface $uri,
        array $headers,
        StreamInterface $content
    ) : PromiseInterface {
        $headers = array_merge($this->headers, $headers);

        $request = new Request('DELETE', $uri, $headers, $content);

        return $this->httpClient->send($request);
    }

    final protected function putRequest(
        UriInterface $uri,
        array $headers,
        StreamInterface $content
    ) : PromiseInterface {
        $headers = array_merge($this->headers, $headers);

        $request = new Request('PUT', $uri, $headers, $content);

        return $this->httpClient->send($request);
    }

    final protected function patchRequest(
        UriInterface $uri,
        array $headers,
        string $content
    ) : PromiseInterface {
        $headers = array_merge($this->headers, $headers);

        $request = new Request('PATCH', $uri, $headers, $content);

        return $this->httpClient->send($request);
    }
}
