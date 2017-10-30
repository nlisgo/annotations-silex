<?php

namespace eLife\HypothesisClient\ApiClient;

use eLife\HypothesisClient\ApiClientTrait;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Uri;

final class UsersClient
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
            Psr7\stream_for('{}')
        );
    }
}
