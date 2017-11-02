<?php

namespace eLife\HypothesisClient\ApiClient;

use Assert\Assert;
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
        // Perform validation checks.
        $this->validateUser($id, $email, $display_name);

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

    public function modifyUser(
        array $headers,
        string $id,
        string $email = null,
        string $display_name = null
    ) : PromiseInterface {
        // Perform validation checks.
        $this->validateUser($id, $email, $display_name);

        return $this->patchRequest(
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

    /**
     * @param string $id
     * @param string|null $email
     * @param string|null $display_name
     * @throws \InvalidArgumentException
     * @return bool
     */
    protected function validateUser(string $id, $email = null, $display_name = null)
    {
        return Assert::lazy()
            // Id must be between 3 and 30 characters.
            ->that($id, 'User id')
            ->minLength(3, 'Value "%s" must be between 3 and 30 characters.')
            ->maxLength(30, 'Value "%s" must be between 3 and 30 characters.')
            // Id is limited to a small set of characters.
            ->that($id, 'User id')
            ->regex('/^[A-Za-z0-9._]+$/', 'Value "%s" does not match expression /^[A-Za-z0-9._]+$/.')
            ->that(array_filter([$email, $display_name]), 'User e-mail and display name')
            ->notEmpty('Either an e-mail address or display name is required.')
            // Email must be valid.
            ->that($email, 'User e-mail')
            ->nullOr()
            ->email()
            // Display name must be no more than 30 characters long.
            ->that($display_name, 'User display name')
            ->nullOr()
            ->minLength(1, 'Value "%s" must be between 1 and 30 characters.')
            ->maxLength(30, 'Value "%s" must be between 1 and 30 characters.')
            ->verifyNow();
    }
}
