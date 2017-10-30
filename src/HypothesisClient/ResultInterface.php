<?php

namespace eLife\HypothesisClient;

use Countable;
use Traversable;

interface ResultInterface extends CastsToArrayInterface, Countable, Traversable
{
    public function search(string $expression);
}
