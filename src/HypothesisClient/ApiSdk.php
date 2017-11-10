<?php

namespace eLife\HypothesisClient;

use BadMethodCallException;
use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\Client\Users;
use eLife\HypothesisClient\Credentials\CredentialsInterface;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;

final class ApiSdk
{
    /** @var CredentialsInterface */
    private $credentials;
    /** @var HttpClientInterface */
    private $httpClient;
    /** @var SerializerAwareInterface */
    private $normalizer;

    /**
     * @var Users
     */
    private $users;

    public function __construct(HttpClientInterface $httpClient, CredentialsInterface $credentials = null)
    {
        $this->httpClient = $httpClient;
        $this->credentials = $credentials;
        $this->normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
        $this->users();
    }

    public function users() : Users
    {
        if (empty($this->users)) {
            $usersClient = new UsersClient($this->httpClient);
            if (!empty($this->credentials)) {
                $usersClient->setCredentials($this->credentials);
            }
            $this->users = new Users($usersClient, $this->normalizer);
        }

        return $this->users;
    }

    public function __call($name, array $args)
    {
        throw new BadMethodCallException("Unknown method: {$name}.");
    }
}
