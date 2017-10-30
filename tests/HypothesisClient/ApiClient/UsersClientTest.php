<?php

namespace tests\eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\HttpClientInterface;
use eLife\HypothesisClient\Result\ArrayResult;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use function GuzzleHttp\Psr7\str;
use PHPUnit_Framework_TestCase;
use TypeError;
use tests\eLife\HypothesisClient\RequestConstraint;

/**
 * @covers \eLife\HypothesisClient\ApiClient\UsersClient
 */
final class UsersClientTest extends PHPUnit_Framework_TestCase
{
    private $httpClient;
    /** @var UsersClient */
    private $usersClient;

    protected function setUp()
    {
        parent::setUp();

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
            ['X-Foo' => 'another_bar', 'User-Agent' => 'HypothesisClient'], 
            '{}'
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $user = $this->usersClient->getUser([], 'user');
        $this->assertSame($response, $user);
    }

    private function request(Request $expected)
    {
        return $this->callback(function($actual) use ($expected) {
            return str($expected) == str($actual);
        });
    }
}
