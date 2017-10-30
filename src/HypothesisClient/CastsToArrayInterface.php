<?php

namespace eLife\HypothesisClient;

use ArrayAccess;

interface CastsToArrayInterface extends ArrayAccess
{
    public function toArray() : array;
}
