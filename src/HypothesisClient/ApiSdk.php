<?php

namespace eLife\HypothesisClient;

use eLife\HypothesisClient\ApiClient\AnnotationsClient;
use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\Credentials\CredentialsInterface;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;

final class ApiSdk
{
    /**
     * @var \eLife\HypothesisClient\HttpClient\HttpClientInterface
     */
    private $httpClient;

    /**
     * @var \eLife\HypothesisClient\Credentials\CredentialsInterface
     */
    private $credentials;

    /**
     * @var \eLife\HypothesisClient\ApiClient\AnnotationsClient
     */
    private $annotationsClient;

    /**
     * @var \eLife\HypothesisClient\ApiClient\UsersClient
     */
    private $usersClient;

    public function __construct(HttpClientInterface $httpClient, CredentialsInterface $credentials = null)
    {
        $this->httpClient = $httpClient;
        $this->credentials = $credentials;
        $this->createAnnotations();
        $this->createUsers();
    }

    public function createAnnotations() : AnnotationsClient
    {
        if (empty($this->annotationsClient)) {
            $this->annotationsClient = new AnnotationsClient($this->httpClient);
            if (!empty($this->credentials)) {
                $this->annotationsClient->setCredentials($this->credentials);
            }
        }

        return $this->annotationsClient;
    }

    public function createUsers() : UsersClient
    {
        if (empty($this->usersClient)) {
            $this->usersClient = new UsersClient($this->httpClient);
            if (!empty($this->credentials)) {
                $this->usersClient->setCredentials($this->credentials);
            }
        }

        return $this->usersClient;
    }

    public function __call($name, array $args)
    {
        throw new \BadMethodCallException("Unknown method: {$name}.");
    }
}
