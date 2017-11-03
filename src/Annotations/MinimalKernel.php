<?php

namespace eLife\Annotations;

interface MinimalKernel
{
    /**
     * @return MinimalKernel
     */
    public function withApp(callable $fn);

    public function run();
}
