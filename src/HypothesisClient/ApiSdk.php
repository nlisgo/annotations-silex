<?php

namespace eLife\HypothesisClient;

use eLife\HypothesisClient\ApiClient\AnnotationsClient;
use eLife\HypothesisClient\ApiClient\UsersClient;

final class ApiSdk
{
    private $httpClient;
    private $annotationsClient;
    private $usersClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->annotationsClient = new AnnotationsClient($this->httpClient);
        $this->usersClient = new UsersClient($this->httpClient);
    }

    public function createAnnotations() : AnnotationsClient
    {
        if (empty($this->annotationsClient)) {
            $this->annotationsClient = new AnnotationsClient($this->httpClient);
        }

        return $this->annotationsClient;
    }

    public function createUsers() : UsersClient
    {
        if (empty($this->usersClient)) {
            $this->usersClient = new UsersClient($this->httpClient);
        }

        return $this->usersClient;
    }

    public function __call($name, array $args)
    {
        throw new \BadMethodCallException("Unknown method: {$name}.");
    }
}
