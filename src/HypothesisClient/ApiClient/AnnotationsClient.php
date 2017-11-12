<?php

namespace eLife\HypothesisClient\ApiClient;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use function GuzzleHttp\Psr7\build_query;

final class AnnotationsClient implements ApiClientInterface
{
    use ApiClientTrait;

    public function listAnnotations(
        array $headers,
        string $user,
        int $page = 1,
        int $perPage = 20,
        bool $descendingOrder = true,
        $group = '__world__'
    ) : PromiseInterface {
        return $this->getRequest(
            Uri::fromParts([
                'path' => 'search',
                'query' => build_query([
                    'user' => $user,
                    'group' => $group,
                    'offset' => ($page - 1) * $perPage,
                    'limit' => $perPage,
                    'order' => $descendingOrder ? 'desc' : 'asc',
                ]),
            ]),
            $headers
        );
    }
}
