<?php

namespace eLife\HypothesisClient\Model;

use InvalidArgumentException;

interface CanBeValidated
{
    /**
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function validate() : bool;
}
