<?php

namespace tests\eLife\HypothesisClient\Credentials;

use eLife\HypothesisClient\Credentials\Credentials;
use PHPUnit_Framework_TestCase;

/**
 * @covers \eLife\HypothesisClient\Credentials\Credentials
 */
class CredentialsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_has_getters()
    {
        $creds = new Credentials('foo', 'baz');
        $this->assertEquals('foo', $creds->getClientId());
        $this->assertEquals('baz', $creds->getSecretKey());
        $this->assertEquals([
            'clientId' => 'foo',
            'secret' => 'baz',
        ], $creds->toArray());
    }
}
