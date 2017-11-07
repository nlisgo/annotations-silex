<?php

namespace eLife\HypothesisClient\ApiClient;

use eLife\HypothesisClient\Credentials\CredentialsInterface;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\HypothesisClient\HttpClient\UserAgentPrependingHttpClient;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\UriInterface;

trait ApiClientTrait
{
    private $httpClient;
    private $headers;
    private $credentials;

    public function __construct(HttpClientInterface $httpClient, array $headers = [])
    {
        $this->httpClient = new UserAgentPrependingHttpClient($httpClient, 'HypothesisClient');
        $this->headers = $headers;
    }

    final public function setCredentials(CredentialsInterface $credentials)
    {
        $this->credentials = $credentials;
        $this->headers['Authorization'] = 'Basic '.base64_encode($credentials->getClientId().':'.$credentials->getSecretKey());
    }

    final public function getCredentials() : CredentialsInterface
    {
        return $this->credentials;
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
        string $content
    ) : PromiseInterface {
        $headers = array_merge($this->headers, $headers);

        $request = new Request('POST', $uri, $headers, $content);

        return $this->httpClient->send($request);
    }

    final protected function putRequest(
        UriInterface $uri,
        array $headers,
        string $content
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
