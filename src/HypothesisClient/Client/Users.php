<?php

namespace eLife\HypothesisClient\Client;

use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\Exception\BadResponse;
use eLife\HypothesisClient\Model\User;
use eLife\HypothesisClient\Result\ResultInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class Users implements ClientInterface
{
    private $normalizer;
    private $usersClient;

    public function __construct(UsersClient $usersClient, DenormalizerInterface $normalizer)
    {
        $this->usersClient = $usersClient;
        $this->normalizer = $normalizer;
    }

    public function get(string $id) : PromiseInterface
    {
        return $this->usersClient
            ->getUser(
                [],
                $id
            )
            ->then(function (ResultInterface $result) {
                return $this->normalizer->denormalize($result->toArray(), User::class);
            });
    }

    /**
     * Store the user by create first then, if user already detected, modify.
     *
     * @param User $user
     *
     * @return PromiseInterface
     */
    public function store(User $user) : PromiseInterface
    {
        try {
            return $this->create($user);
        } catch (BadResponse $exception) {
            $body = (string) $exception->getResponse()->getBody();
            // If username exists then attempt to update the user.
            if (preg_match('/user with username [^\s]+ already exists/', $body)) {
                return $this->update($user);
            } else {
                throw $exception;
            }
        }
    }

    public function create(User $user) : PromiseInterface
    {
        return $this->usersClient
            ->createUser(
                [],
                $user->getId(),
                $user->getEmail(),
                $user->getDisplayName()
            )
            ->then(function (ResultInterface $result) {
                return $this->normalizer->denormalize($result->toArray(), User::class);
            });
    }

    public function update(User $user) : PromiseInterface
    {
        return $this->usersClient
            ->updateUser(
                [],
                $user->getId(),
                $user->getEmail(),
                $user->getDisplayName()
            )
            ->then(function (ResultInterface $result) {
                return $this->normalizer->denormalize($result->toArray(), User::class);
            });
    }
}
