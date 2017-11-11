<?php

namespace eLife\HypothesisClient\Client;

use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\Exception\BadResponse;
use eLife\HypothesisClient\Model\User;
use eLife\HypothesisClient\Result\ResultInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function GuzzleHttp\Promise\exception_for;

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
        return $this->create($user)
            ->otherwise(function ($reason) use ($user) {
                $exception = exception_for($reason);
                if ($exception instanceof BadResponse && preg_match('/user with username [^\s]+ already exists/', (string) $exception->getResponse()->getBody())) {
                    return $this->update($user);
                } else {
                    throw $exception;
                }
            });
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
                $user = $this->normalizer->denormalize($result->toArray(), User::class);
                $user->setNew();

                return $user;
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
