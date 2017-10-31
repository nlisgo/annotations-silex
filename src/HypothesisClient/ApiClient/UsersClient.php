<?php

namespace eLife\HypothesisClient\ApiClient;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;

final class UsersClient implements ApiClientInterface
{
    use ApiClientTrait;

    public function getUser(
        array $headers,
        string $id
    ) : PromiseInterface {
        return $this->patchRequest(
            Uri::fromParts([
                'path' => 'api/user/'.$id,
            ]),
            $headers,
            '{}'
        );
    }

    public function createUser(
        array $headers,
        string $id,
        string $email,
        string $display_name
    ) : PromiseInterface {
        return $this->postRequest(
            Uri::fromParts([
                'path' => 'api/users',
            ]),
            $headers,
            json_encode([
                'authority' => $this->getCredentials()->getAuthority(),
                'username' => $id,
                'email' => $email,
                'display_name' => $display_name,
            ])
        );
    }

    public function editUser(
        array $headers,
        string $id,
        string $email = null,
        string $display_name = null
    ) : PromiseInterface {
        return $this->postRequest(
            Uri::fromParts([
                'path' => 'api/user/'.$id,
            ]),
            $headers,
            json_encode(array_filter([
                'email' => $email,
                'display_name' => $display_name,
            ]))
        );
    }
}
