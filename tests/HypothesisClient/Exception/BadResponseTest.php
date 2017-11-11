<?php

namespace tests\eLife\HypothesisClient\Exception;

use ArgumentCountError;
use eLife\HypothesisClient\Exception\BadResponse;
use eLife\HypothesisClient\Exception\HttpProblem;
use Exception;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TypeError;

/**
 * @covers \eLife\HypothesisClient\Exception\BadResponse
 */
class BadResponseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_requires_a_message()
    {
        try {
            $this->getMockBuilder(BadResponse::class)->getMock();
            $this->fail('A message is required');
        } catch (ArgumentCountError $error) {
            // ArgumentCountError ^7.1
            $this->assertTrue(true, 'A message is required');
            $this->assertContains('Too few arguments', $error->getMessage());
        } catch (TypeError $error) {
            $this->assertTrue(true, 'A message is required');
            $this->assertContains('must be of the type string', $error->getMessage());
        }
        $e = new BadResponse('foo', $this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class));
        $this->assertEquals('foo', $e->getMessage());
    }

    /**
     * @test
     */
    public function it_requires_a_request()
    {
        try {
            $this->getMockBuilder(BadResponse::class)
                ->setConstructorArgs(['foo'])
                ->getMock();
            $this->fail('A request is required');
        } catch (ArgumentCountError $error) {
            // ArgumentCountError ^7.1
            $this->assertTrue(true, 'A request is required');
            $this->assertContains('Too few arguments', $error->getMessage());
        } catch (TypeError $error) {
            $this->assertTrue(true, 'A request is required');
            $this->assertContains('none given', $error->getMessage());
        }
        $request = $this->createMock(RequestInterface::class);
        $e = new BadResponse('foo', $request, $this->createMock(ResponseInterface::class));
        $this->assertSame($request, $e->getRequest());
    }

    /**
     * @test
     */
    public function it_requires_a_response()
    {
        try {
            $this->getMockBuilder(BadResponse::class)
                ->setConstructorArgs(['foo', $this->createMock(RequestInterface::class)])
                ->getMock();
            $this->fail('A response is required');
        } catch (ArgumentCountError $error) {
            // ArgumentCountError ^7.1
            $this->assertTrue(true, 'A response is required');
            $this->assertContains('Too few arguments', $error->getMessage());
        } catch (TypeError $error) {
            $this->assertTrue(true, 'A response is required');
            $this->assertContains('none given', $error->getMessage());
        }
        $response = $this->createMock(ResponseInterface::class);
        $e = new BadResponse('foo', $this->createMock(RequestInterface::class), $response);
        $this->assertSame($response, $e->getResponse());
    }

    /**
     * @test
     */
    public function it_is_an_instance_of_http_problem()
    {
        $e = new BadResponse('foo', $this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class));
        $this->assertInstanceOf(HttpProblem::class, $e);
    }

    /**
     * @test
     */
    public function it_may_not_have_a_previous_exception()
    {
        $e = new BadResponse('foo', $this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class));
        $this->assertNull($e->getPrevious());
    }

    /**
     * @test
     */
    public function it_may_have_a_previous_exception()
    {
        $previous = $this->createMock(Exception::class);
        $e = new BadResponse('foo', $this->createMock(RequestInterface::class), $this->createMock(ResponseInterface::class), $previous);
        $this->assertEquals($previous, $e->getPrevious());
    }
}
