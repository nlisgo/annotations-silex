<?php

namespace tests\eLife\HypothesisClient\Exception;

use eLife\HypothesisClient\Exception\ApiTimeout;
use eLife\HypothesisClient\Exception\NetworkProblem;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @covers \eLife\HypothesisClient\Exception\ApiTimeout
 */
class ApiTimeoutTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_is_an_instance_of_network_problem()
    {
        $e = new ApiTimeout('foo', $this->createMock(RequestInterface::class));
        $this->assertInstanceOf(NetworkProblem::class, $e);
    }
}
