<?php

namespace tests\eLife\HypothesisClient\Exception;

use ArgumentCountError;
use eLife\HypothesisClient\Exception\ApiException;
use eLife\HypothesisClient\Exception\HttpProblem;
use Exception;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use TypeError;

/**
 * @covers \eLife\HypothesisClient\Exception\HttpProblem
 */
class HttpProblemTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_requires_a_message()
    {
        try {
            $this->getMockBuilder(HttpProblem::class)->getMock();
            $this->fail('A message is required');
        } catch (ArgumentCountError $error) {
            // ArgumentCountError ^7.1
            $this->assertTrue(true, 'A message is required');
            $this->assertContains('Too few arguments', $error->getMessage());
        } catch (TypeError $error) {
            $this->assertTrue(true, 'A message is required');
            $this->assertContains('none given', $error->getMessage());
        }
        $e = $this->getMockBuilder(HttpProblem::class)
            ->setConstructorArgs(['foo', $this->createMock(RequestInterface::class)])
            ->getMockForAbstractClass();
        $this->assertEquals('foo', $e->getMessage());
    }

    /**
     * @test
     */
    public function it_requires_a_request()
    {
        try {
            $this->getMockBuilder(HttpProblem::class)
                ->setConstructorArgs(['foo'])
                ->getMockForAbstractClass();
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
        $e = $this->getMockBuilder(HttpProblem::class)
            ->setConstructorArgs(['foo', $request])
            ->getMockForAbstractClass();
        $this->assertSame($request, $e->getRequest());
    }

    /**
     * @test
     */
    public function it_is_an_instance_of_api_exception()
    {
        $e = $this->getMockBuilder(HttpProblem::class)
            ->setConstructorArgs(['foo', $this->createMock(RequestInterface::class)])
            ->getMockForAbstractClass();
        $this->assertInstanceOf(ApiException::class, $e);
    }

    /**
     * @test
     */
    public function it_may_not_have_a_previous_exception()
    {
        $e = $this->getMockBuilder(HttpProblem::class)
            ->setConstructorArgs(['foo', $this->createMock(RequestInterface::class)])
            ->getMockForAbstractClass();
        $this->assertNull($e->getPrevious());
    }

    /**
     * @test
     */
    public function it_may_have_a_previous_exception()
    {
        $previous = $this->createMock(Exception::class);
        $e = $this->getMockBuilder(HttpProblem::class)
            ->setConstructorArgs(['foo', $this->createMock(RequestInterface::class), $previous])
            ->getMockForAbstractClass();
        $this->assertEquals($previous, $e->getPrevious());
    }
}
