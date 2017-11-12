<?php

namespace tests\eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\Credentials\Credentials;
use eLife\HypothesisClient\Credentials\CredentialsInterface;
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
        $this->usersClient = new UsersClient(
            $this->httpClient,
            ['X-Foo' => 'bar']
        );
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
            $this->assertContains(
                'must implement interface '.HttpClientInterface::class.', string given',
                $error->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function it_gets_a_user()
    {
        $request = new Request(
            'PATCH',
            'users/user',
            ['X-Foo' => 'bar', 'User-Agent' => 'HypothesisClient'],
            '{}'
        );
        $response = new FulfilledPromise(
            new ArrayResult(['foo' => ['bar', 'baz']])
        );
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
    public function it_creates_a_user_with_credentials()
    {
        // Some operations require credentials, react if they are missing.
        try {
            $this->usersClient->createUser([], 'userid', 'email@email.com', 'display_name');
            $this->fail('Credentials are required, if requested');
        } catch (TypeError $error) {
            $this->assertTrue(true, 'Credentials are required, if requested');
            $this->assertContains(CredentialsInterface::class.', null returned', $error->getMessage());
        }
        $this->usersClient->setCredentials(new Credentials('client_id', 'secret_key', 'authority'));
        $request = new Request(
            'POST',
            'users',
            ['X-Foo' => 'bar', 'Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            json_encode(['authority' => 'authority', 'username' => 'userid', 'email' => 'email@email.com', 'display_name' => 'display_name'])
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->usersClient->createUser([], 'userid', 'email@email.com', 'display_name'));
    }

    /**
     * @test
     */
    public function it_modifies_a_user()
    {
        $this->usersClient->setCredentials(new Credentials('client_id', 'secret_key', 'authority'));
        $request = new Request(
            'PATCH',
            'users/userid',
            ['X-Foo' => 'bar', 'Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            json_encode(['email' => 'email@email.com'])
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->usersClient->updateUser([], 'userid', 'email@email.com'));
    }

    /**
     * @test
     */
    public function it_may_have_credentials()
    {
        $request = new Request(
            'PATCH',
            'users/user',
            ['X-Foo' => 'bar', 'Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            '{}'
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->usersClient->setCredentials(new Credentials('client_id', 'secret_key', 'authority'));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->usersClient->getUser([], 'user'));
    }
}
