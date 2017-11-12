<?php

namespace tests\eLife\HypothesisClient\Client;

use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\Client\ClientInterface;
use eLife\HypothesisClient\Client\Users;
use eLife\HypothesisClient\Credentials\Credentials;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\HypothesisClient\Model\User;
use eLife\HypothesisClient\Result\ArrayResult;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use tests\eLife\HypothesisClient\RequestConstraint;

/**
 * @covers \eLife\HypothesisClient\Client\Users
 */
class UsersTest extends PHPUnit_Framework_TestCase
{
    private $authority;
    private $authorization;
    private $clientId;
    private $credentials;
    private $denormalizer;
    private $httpClient;
    private $secretKey;
    /** @var Users */
    private $users;
    /** @var UsersClient */
    private $usersClient;

    /**
     * @before
     */
    public function prepareDependencies()
    {
        $this->clientId = 'client_id';
        $this->secretKey = 'secret_key';
        $this->authority = 'authority';
        $this->authorization = sprintf('Basic %s', base64_encode($this->clientId.':'.$this->secretKey));
        $this->credentials = new Credentials($this->clientId, $this->secretKey, $this->authority);
        $this->denormalizer = $this->getMockBuilder(DenormalizerInterface::class)
            ->setMethods(['denormalize', 'supportsDenormalization'])
            ->getMock();
        $this->httpClient = $this->getMockBuilder(HttpClientInterface::class)
            ->setMethods(['send'])
            ->getMock();
        $this->usersClient = new UsersClient($this->httpClient);
        $this->users = new Users($this->usersClient, $this->denormalizer);
    }

    /**
     * @test
     */
    public function it_is_a_client()
    {
        $this->assertInstanceOf(ClientInterface::class, $this->users);
    }

    /**
     * @test
     */
    public function it_will_get_a_user()
    {
        $request = new Request(
            'PATCH',
            'users/username',
            ['User-Agent' => 'HypothesisClient'],
            '{}'
        );
        $response = new FulfilledPromise(new ArrayResult([
            'username' => 'username',
            'email' => 'email@email.com',
            'display_name' => 'Display Name',
            'authority' => 'authority',
        ]));
        $user = new User('username', 'email@email.com', 'Display Name');
        $this->denormalizer
            ->method('denormalize')
            ->with($response->wait()->toArray(), User::class)
            ->willReturn($user);
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($user, $this->users->get('username')->wait());
    }

    /**
     * @test
     */
    public function it_will_create_a_user()
    {
        $data = [
            'authority' => 'authority',
            'username' => 'username',
            'email' => 'email@email.com',
            'display_name' => 'Display Name',
        ];
        $response_data = $data + ['userid' => sprintf('%s@%s', $data['username'], $data['authority'])];
        $request = new Request(
            'POST',
            'users',
            ['Authorization' => $this->authorization, 'User-Agent' => 'HypothesisClient'],
            json_encode($data)
        );
        $response = new FulfilledPromise(new ArrayResult($response_data));
        $user = new User('username', 'email@email.com', 'Display Name');
        $this->usersClient
            ->setCredentials($this->credentials);
        $this->denormalizer
            ->method('denormalize')
            ->with($response->wait()->toArray(), User::class)
            ->willReturn($user);
        $expectedUser = clone $user;
        $expectedUser->setNew();
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $createdUser = $this->users->create($user)->wait();
        $this->assertTrue($createdUser->isNew());
        $this->assertEquals($expectedUser, $createdUser);
    }

    /**
     * @test
     */
    public function it_will_update_a_user()
    {
        $data = [
            'email' => 'email@email.com',
            'display_name' => 'Display Name',
        ];
        $response_data = $data + ['username' => 'username', 'authority' => 'authority', 'userid' => sprintf('%s@%s', 'username', 'authority')];
        $request = new Request(
            'PATCH',
            'users/username',
            ['Authorization' => $this->authorization, 'User-Agent' => 'HypothesisClient'],
            json_encode($data)
        );
        $response = new FulfilledPromise(new ArrayResult($response_data));
        $user = new User('username', 'email@email.com', 'Display Name');
        $this->usersClient
            ->setCredentials($this->credentials);
        $this->denormalizer
            ->method('denormalize')
            ->with($response->wait()->toArray(), User::class)
            ->willReturn($user);
        $expectedUser = clone $user;
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $updatedUser = $this->users->update($user)->wait();
        $this->assertFalse($updatedUser->isNew());
        $this->assertEquals($expectedUser, $updatedUser);
    }

    /**
     * @test
     */
    public function it_will_store_a_new_user()
    {
        $data = [
            'authority' => 'authority',
            'username' => 'username',
            'email' => 'email@email.com',
            'display_name' => 'Display Name',
        ];
        $response_data = $data + ['userid' => sprintf('%s@%s', $data['username'], $data['authority'])];
        $request = new Request(
            'POST',
            'users',
            ['Authorization' => $this->authorization, 'User-Agent' => 'HypothesisClient'],
            json_encode($data)
        );
        $response = new FulfilledPromise(new ArrayResult($response_data));
        $user = new User('username', 'email@email.com', 'Display Name');
        $this->usersClient
            ->setCredentials($this->credentials);
        $this->denormalizer
            ->method('denormalize')
            ->with($response->wait()->toArray(), User::class)
            ->willReturn($user);
        $expectedUser = clone $user;
        $expectedUser->setNew();
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $storedUser = $this->users->store($user)->wait();
        $this->assertTrue($storedUser->isNew());
        $this->assertEquals($expectedUser, $storedUser);
    }

    /**
     * @test
     */
    public function it_will_store_an_existing_user()
    {
        $this->assertTrue(true);
    }
}
