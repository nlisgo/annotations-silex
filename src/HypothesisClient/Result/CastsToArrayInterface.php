<?php

namespace eLife\HypothesisClient\Result;

use ArrayAccess;

interface CastsToArrayInterface extends ArrayAccess
{
    public function toArray() : array;
}
