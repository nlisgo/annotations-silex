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
        $creds = new Credentials('foo', 'baz', 'authority');
        $this->assertEquals('foo', $creds->getClientId());
        $this->assertEquals('baz', $creds->getSecretKey());
        $this->assertEquals('authority', $creds->getAuthority());
        $this->assertEquals([
            'clientId' => 'foo',
            'secret' => 'baz',
            'authority' => 'authority',
        ], $creds->toArray());
    }

    public function it_may_not_have_an_authority()
    {
        $creds = new Credentials('foo', 'baz');
        $this->assertNull($creds->getAuthority());
        $this->assertEquals([
            'clientId' => 'foo',
            'secret' => 'baz',
            'authority' => null,
        ], $creds->toArray());
    }
}
