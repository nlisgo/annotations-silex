<?php

namespace eLife\HypothesisClient\Credentials;

use Serializable;

class Credentials implements CredentialsInterface, Serializable
{
    private $clientId;
    private $secret;
    private $authority;

    public function __construct(string $clientId, string $secret, string $authority)
    {
        $this->clientId = trim($clientId);
        $this->secret = trim($secret);
        $this->authority = trim($authority);
    }

    public function getClientId() : string
    {
        return $this->clientId;
    }

    public function getSecretKey() : string
    {
        return $this->secret;
    }

    public function getAuthority() : string
    {
        return $this->authority;
    }

    public function toArray() : array
    {
        return [
            'clientId' => $this->getClientId(),
            'secret' => $this->getSecretKey(),
            'authority' => $this->getAuthority(),
        ];
    }

    public function serialize() : string
    {
        return json_encode($this->toArray());
    }

    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);

        $this->clientId = $data['clientId'];
        $this->secret = $data['secret'];
        $this->authority = $data['authority'];
    }
}
