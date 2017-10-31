<?php

namespace eLife\HypothesisClient\Result;

use Countable;
use Traversable;

interface ResultInterface extends CastsToArrayInterface, Countable, Traversable
{
    public function search(string $expression);
}
