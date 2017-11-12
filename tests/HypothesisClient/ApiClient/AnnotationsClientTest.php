<?php

namespace tests\eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\ApiClient\AnnotationsClient;
use eLife\HypothesisClient\Credentials\Credentials;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\HypothesisClient\Result\ArrayResult;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase;
use tests\eLife\HypothesisClient\RequestConstraint;
use TypeError;

/**
 * @covers \eLife\HypothesisClient\ApiClient\AnnotationsClient
 */
final class AnnotationsClientTest extends PHPUnit_Framework_TestCase
{
    private $httpClient;
    /** @var AnnotationsClient */
    private $annotationsClient;

    /**
     * @before
     */
    protected function setUpClient()
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->annotationsClient = new AnnotationsClient($this->httpClient, ['X-Foo' => 'bar']);
    }

    /**
     * @test
     */
    public function it_requires_a_http_client()
    {
        try {
            new AnnotationsClient('foo');
            $this->fail('A HttpClient is required');
        } catch (TypeError $error) {
            $this->assertTrue(true, 'A HttpClient is required');
            $this->assertContains('must implement interface '.HttpClientInterface::class.', string given', $error->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_lists_annotations()
    {
        $request = new Request('GET', 'search?user=list&group=__world__&offset=0&limit=20&order=desc',
            ['X-Foo' => 'bar', 'User-Agent' => 'HypothesisClient']);
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->annotationsClient->listAnnotations([], 'list', 1, 20, true, '__world__'));
    }

    /**
     * @test
     */
    public function it_may_have_credentials()
    {
        $request = new Request('GET', 'search?user=list&group=__world__&offset=0&limit=20&order=desc',
            ['X-Foo' => 'bar', 'Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient']);
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->annotationsClient->setCredentials(new Credentials('client_id', 'secret_key', 'authority'));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->annotationsClient->listAnnotations([], 'list', 1, 20, true, '__world__'));
    }
}
