<?php

namespace tests\eLife\HypothesisClient;

use eLife\HypothesisClient\ApiSdk;
use eLife\HypothesisClient\Client\Users;
use eLife\HypothesisClient\Credentials\Credentials;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\HypothesisClient\Model\User;
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
    public function it_creates_a_users_client()
    {
        $this->assertInstanceOf(
            Users::class,
            (new ApiSdk($this->getMockBuilder(HttpClientInterface::class)->getMock()))->users()
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
            ->setConstructorArgs(['client_id', 'secret_key', 'authority'])
            ->getMock();

        $credentials->expects($this->atLeastOnce())->method('getClientId')->willReturn('client_id');
        $credentials->expects($this->atLeastOnce())->method('getSecretKey')->willReturn('secret_key');

        $sdk = (new ApiSdk(
            $httpClient,
            $credentials
        ));

        $request = new Request(
            'PATCH',
            'users/username',
            ['Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            '{}'
        );
        $response = new FulfilledPromise(new ArrayResult([
            'username' => 'username',
            'email' => 'email@email.com',
            'display_name' => 'Display Name',
            'authority' => 'authority',
        ]));

        $user = new User('username', 'email@email.com', 'Display Name');
        $httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($user, $sdk->users()->get('username')->wait());
    }
}
