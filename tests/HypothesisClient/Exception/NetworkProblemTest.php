<?php

namespace tests\eLife\HypothesisClient\Exception;

use eLife\HypothesisClient\Exception\HttpProblem;
use eLife\HypothesisClient\Exception\NetworkProblem;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @covers \eLife\HypothesisClient\Exception\NetworkProblem
 */
class NetworkProblemTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_is_an_instance_of_http_problem()
    {
        $e = new NetworkProblem('foo', $this->createMock(RequestInterface::class));
        $this->assertInstanceOf(HttpProblem::class, $e);
    }
}
