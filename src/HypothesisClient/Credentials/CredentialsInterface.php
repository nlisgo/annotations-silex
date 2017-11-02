<?php

namespace eLife\HypothesisClient\Credentials;

/**
 * Provides access to the Hypothesis credentials used for accessing Hypothesis
 * API: client ID, secret access key. These credentials are used to authenticate
 * requests to Hypothesis API.
 */
interface CredentialsInterface
{
    /**
     * Returns the access client ID for this credentials object.
     *
     * @return string
     */
    public function getClientId() : string;

    /**
     * Returns the secret access key for this credentials object.
     *
     * @return string
     */
    public function getSecretKey() : string;

    /**
     * Returns the authority for this credentials object.
     *
     * @return string
     */
    public function getAuthority() : string;

    /**
     * Converts the credentials to an associative array.
     *
     * @return array
     */
    public function toArray() : array;
}
