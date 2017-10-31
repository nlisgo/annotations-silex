<?php

namespace eLife\HypothesisClient\ApiClient;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;

final class UsersClient implements ApiClientInterface
{
    use ApiClientTrait;

    public function getUser(
        array $headers,
        string $user
    ) : PromiseInterface {
        return $this->patchRequest(
            Uri::fromParts([
                'path' => 'api/user/'.$user,
            ]),
            $headers,
            '{}'
        );
    }
}
