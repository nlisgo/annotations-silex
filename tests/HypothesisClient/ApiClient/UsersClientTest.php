<?php

namespace tests\eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\Credentials\Credentials;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\HypothesisClient\Result\ArrayResult;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase;
use tests\eLife\HypothesisClient\RequestConstraint;
use TypeError;

/**
 * @covers \eLife\HypothesisClient\ApiClient\UsersClient
 */
final class UsersClientTest extends PHPUnit_Framework_TestCase
{
    private $httpClient;
    /** @var UsersClient */
    private $usersClient;

    /**
     * @before
     */
    protected function setUpClient()
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->usersClient = new UsersClient($this->httpClient, ['X-Foo' => 'bar']);
    }

    /**
     * @test
     */
    public function it_requires_a_http_client()
    {
        try {
            new UsersClient('foo');
            $this->fail('A HttpClient is required');
        } catch (TypeError $error) {
            $this->assertTrue(true, 'A HttpClient is required');
            $this->assertContains('must implement interface '.HttpClientInterface::class.', string given', $error->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_gets_a_user()
    {
        $request = new Request(
            'PATCH',
            'api/user/user',
            ['X-Foo' => 'bar', 'User-Agent' => 'HypothesisClient'],
            '{}'
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertSame($response, $this->usersClient->getUser([], 'user'));
    }

    /**
     * @test
     */
    public function it_may_have_credentials()
    {
        $request = new Request(
            'PATCH',
            'api/user/user',
            ['X-Foo' => 'bar', 'Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            '{}'
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->usersClient->setCredentials(new Credentials('client_id', 'secret_key'));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->usersClient->getUser([], 'user'));
    }
}
