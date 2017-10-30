<?php

namespace tests\eLife\HypothesisClient\Result;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use eLife\HypothesisClient\Result\HttpResult;
use GuzzleHttp\Psr7\Response;
use IteratorAggregate;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;
use TypeError;
use UnexpectedValueException;

/**
 * @covers \eLife\HypothesisClient\Result\HttpResult
 */
final class HttpResultTest extends PHPUnit_Framework_TestCase
{
    private $data;
    /** @var ResponseInterface */
    private $response;
    /** @var HttpResult */
    private $result;

    /**
     * @before
     */
    protected function setUpResult()
    {
        $this->data = ['one' => ['two', 'three']];
        $this->response = new Response(200, [], json_encode($this->data));
        $this->result = HttpResult::fromResponse($this->response);
    }

    /**
     * @test
     */
    public function it_casts_to_any_array()
    {
        $this->assertEquals($this->data, $this->result->toArray());
    }

    /**
     * @test
     */
    public function it_has_a_response()
    {
        $this->assertEquals($this->response, $this->result->getResponse());
    }

    /**
     * @test
     */
    public function it_can_be_searched()
    {
        $this->assertEquals(array_pop($this->data['one']), $this->result->search('one[1]'));
    }

    /**
     * @test
     */
    public function it_can_be_counted()
    {
        $this->assertInstanceOf(Countable::class, $this->result);
        $this->assertEquals(count($this->data), $this->result->count());
    }

    /**
     * @test
     */
    public function it_can_be_iterated()
    {
        $this->assertInstanceOf(IteratorAggregate::class, $this->result);
        $this->assertEquals(new ArrayIterator($this->data), $this->result->getIterator());
    }

    /**
     * @test
     */
    public function it_can_be_accessed_like_an_array()
    {
        $this->assertInstanceOf(ArrayAccess::class, $this->result);
        $this->assertTrue($this->result->offsetExists('one'));
        $this->assertEquals($this->data['one'], $this->result->offsetGet('one'));
    }

    /**
     * @test
     */
    public function it_is_immutable()
    {
        try {
            $this->result->offsetSet('one', 'two');
            $this->fail('Value cannot be adjusted once set');
        } catch (BadMethodCallException $exception) {
            $this->assertTrue(true, 'Value cannot be adjusted once set');
        }
        try {
            $this->result->offsetUnset('one');
            $this->fail('Value cannot be adjusted once set');
        } catch (BadMethodCallException $exception) {
            $this->assertTrue(true, 'Value cannot be adjusted once set');
        }
    }

    /**
     * @test
     */
    public function it_requires_a_http_response()
    {
        $this->expectException(TypeError::class);

        HttpResult::fromResponse('foo');
    }

    /**
     * @test
     */
    public function it_requires_data()
    {
        $this->expectException(UnexpectedValueException::class);

        HttpResult::fromResponse(new Response(200));
    }

    /**
     * @test
     */
    public function it_requires_json_data()
    {
        $this->expectException(UnexpectedValueException::class);

        HttpResult::fromResponse(new Response(200, [], 'foo'));
    }
}
