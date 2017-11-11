<?php

namespace tests\eLife\HypothesisClient\Exception;

use ArgumentCountError;
use eLife\HypothesisClient\Exception\ApiException;
use Exception;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \eLife\HypothesisClient\Exception\ApiException
 */
class ApiExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_requires_a_message()
    {
        try {
            $this->getMockBuilder(ApiException::class)->getMock();
            $this->fail('A message is required');
        } catch (ArgumentCountError $error) {
            // ArgumentCountError ^7.1
            $this->assertTrue(true, 'A message is required');
            $this->assertContains('Too few arguments', $error->getMessage());
        } catch (TypeError $error) {
            $this->assertTrue(true, 'A message is required');
            $this->assertContains('must be of the type string', $error->getMessage());
        }
        $e = new ApiException('foo');
        $this->assertEquals('foo', $e->getMessage());
    }

    /**
     * @test
     */
    public function it_has_an_error_code_of_zero()
    {
        $e = $this->createMock(ApiException::class);
        $this->assertEquals(0, $e->getCode());
    }

    /**
     * @test
     */
    public function it_is_an_instance_of_runtime_exception()
    {
        $e = new ApiException('foo');
        $this->assertInstanceOf(RuntimeException::class, $e);
    }

    /**
     * @test
     */
    public function it_may_not_have_a_previous_exception()
    {
        $e = new ApiException('foo');
        $this->assertNull($e->getPrevious());
    }

    /**
     * @test
     */
    public function it_may_have_a_previous_exception()
    {
        $previous = $this->createMock(Exception::class);
        $e = new ApiException('foo', $previous);
        $this->assertEquals($previous, $e->getPrevious());
    }
}
