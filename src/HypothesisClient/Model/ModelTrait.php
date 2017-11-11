<?php

namespace eLife\HypothesisClient\Model;

trait ModelTrait
{
    protected $new = false;

    public function isNew(): bool
    {
        return $this->new;
    }

    public function setNew()
    {
        $this->new = true;
    }
}
