<?php

namespace eLife\HypothesisClient\Result;

use ArrayIterator;
use BadMethodCallException;
use Iterator;
use IteratorAggregate;
use function JmesPath\search;

final class ArrayResult implements IteratorAggregate, ResultInterface
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray() : array
    {
        return $this->data;
    }

    public function search(string $expression)
    {
        if (false === function_exists('JmesPath\search')) {
            throw new BadMethodCallException('Requires mtdowling/jmespath.php');
        }

        return search($expression, $this->data);
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        if (false === $this->offsetExists($offset)) {
            return null;
        }

        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Object is immutable');
    }

    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Object is immutable');
    }

    public function getIterator() : Iterator
    {
        return new ArrayIterator($this->data);
    }

    public function count() : int
    {
        return count($this->data);
    }
}
