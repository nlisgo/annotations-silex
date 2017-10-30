<?php

namespace eLife\HypothesisClient\Credentials;

use Serializable;

class Credentials implements CredentialsInterface, Serializable
{
    private $clientId;
    private $secret;

    public function __construct($clientId, $secret)
    {
        $this->clientId = trim($clientId);
        $this->secret = trim($secret);
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getSecretKey()
    {
        return $this->secret;
    }

    public function toArray()
    {
        return [
            'clientId' => $this->clientId,
            'secret' => $this->secret,
        ];
    }

    public function serialize()
    {
        return json_encode($this->toArray());
    }

    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);

        $this->clientId = $data['clientId'];
        $this->secret = $data['secret'];
    }
}
