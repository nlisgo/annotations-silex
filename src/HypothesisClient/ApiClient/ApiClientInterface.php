<?php

namespace eLife\HypothesisClient\ApiClient;

use eLife\HypothesisClient\Credentials\CredentialsInterface;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;

interface ApiClientInterface
{
    public function __construct(HttpClientInterface $httpClient, array $headers = []);

    public function setCredentials(CredentialsInterface $credentials);

    public function getCredentials() : CredentialsInterface;
}
