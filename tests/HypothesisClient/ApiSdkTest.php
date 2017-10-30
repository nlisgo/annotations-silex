<?php

namespace tests\eLife\HypothesisClient;

use eLife\HypothesisClient\ApiClient\AnnotationsClient;
use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\HttpClientInterface;
use eLife\HypothesisClient\ApiSdk;
use PHPUnit_Framework_TestCase;

/**
 * @covers \eLife\HypothesisClient\ApiSdk
 */
final class ApiSdkTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \BadMethodCallException
     */
    public function ensure_missing_method_throws_exception()
    {
        (new ApiSdk($this->getMockBuilder(HttpClientInterface::class)->getMock()))->foo();
    }

    /**
     * @test
     */
    public function it_creates_an_annotations_client()
    {
        $this->assertInstanceOf(
            AnnotationsClient::class,
            (new ApiSdk($this->getMockBuilder(HttpClientInterface::class)->getMock()))->createAnnotations()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_users_client()
    {
        $this->assertInstanceOf(
            UsersClient::class,
            (new ApiSdk($this->getMockBuilder(HttpClientInterface::class)->getMock()))->createUsers()
        );
    }
}