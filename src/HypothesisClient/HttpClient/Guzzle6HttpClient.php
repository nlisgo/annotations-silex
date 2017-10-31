<?php

namespace eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\Exception\ApiException;
use eLife\HypothesisClient\Exception\ApiTimeout;
use eLife\HypothesisClient\Exception\BadResponse;
use eLife\HypothesisClient\Exception\NetworkProblem;
use eLife\HypothesisClient\Result\HttpResult;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\default_user_agent;
use function GuzzleHttp\Promise\exception_for;

class Guzzle6HttpClient implements HttpClientInterface
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function send(RequestInterface $request) : PromiseInterface
    {
        $request = $request->withHeader('User-Agent', trim(($request->getHeader('User-Agent')[0] ?? '').' '.default_user_agent()));

        return $this->client->sendAsync($request, ['http_errors' => true])
            ->then(
                function (ResponseInterface $response) {
                    return HttpResult::fromResponse($response);
                }
            )->otherwise(
                function ($reason) {
                    $e = exception_for($reason);

                    if ($e instanceof BadResponseException) {
                        throw new BadResponse($e->getMessage(), $e->getRequest(), $e->getResponse(), $e);
                    } elseif ($e instanceof RequestException) {
                        if ($e instanceof ConnectException && CURLE_OPERATION_TIMEOUTED === ($e->getHandlerContext()['errno'] ?? null)) {
                            throw new ApiTimeout($e->getMessage(), $e->getRequest(), $e);
                        }

                        throw new NetworkProblem($e->getMessage(), $e->getRequest(), $e);
                    }

                    throw new ApiException($e->getMessage(), $e);
                }
            );
    }
}
