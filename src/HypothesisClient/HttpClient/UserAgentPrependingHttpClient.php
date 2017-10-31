<?php

namespace eLife\HypothesisClient\HttpClient;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

final class UserAgentPrependingHttpClient implements HttpClientInterface
{
    private $httpClient;
    private $userAgent;

    public function __construct(HttpClientInterface $httpClient, string $userAgent)
    {
        $this->httpClient = $httpClient;
        $this->userAgent = $userAgent;
    }

    public function send(RequestInterface $request) : PromiseInterface
    {
        $request = $request->withHeader('User-Agent', trim(implode(' ', [$this->userAgent, $request->getHeader('User-Agent')[0] ?? ''])));

        return $this->httpClient->send($request);
    }
}
