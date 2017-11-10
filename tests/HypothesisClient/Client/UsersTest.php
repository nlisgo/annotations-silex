<?php

namespace tests\eLife\HypothesisClient\Client;

use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\Client\ClientInterface;
use eLife\HypothesisClient\Client\Users;
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
    private $clientId;
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
            'api/users/username',
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
}

