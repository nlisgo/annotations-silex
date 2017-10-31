<?php

namespace tests\eLife\HypothesisClient;

use eLife\HypothesisClient\ApiClient\AnnotationsClient;
use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\ApiSdk;
use eLife\HypothesisClient\Credentials\Credentials;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\HypothesisClient\Result\ArrayResult;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
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

    /**
     * @test
     */
    public function it_may_have_credentials()
    {
        $httpClient = $this->getMockBuilder(HttpClientInterface::class)
            ->getMock();
        $credentials = $this->getMockBuilder(Credentials::class)
            ->setConstructorArgs(['client_id', 'secret_key'])
            ->getMock();

        $credentials->expects($this->exactly(2))->method('getClientId')->willReturn('client_id');
        $credentials->expects($this->exactly(2))->method('getSecretKey')->willReturn('client_id');

        $sdk = (new ApiSdk(
            $httpClient,
            $credentials
        ));

        $usersClient = $sdk->createUsers();

        $request = new Request(
            'PATCH',
            'api/user/user',
            ['Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            '{}'
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $usersClient->getUser([], 'user'));
    }
}
