<?php

namespace tests\eLife\Annotations;

use eLife\Annotations\Kernel;
use Silex\WebTestCase as SilexWebTestCase;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class WebTestCase extends SilexWebTestCase
{
    protected $isLocal;
    /** @var Kernel */
    protected $kernel;
    public function createApplication() : HttpKernelInterface
    {
        $this->kernel = new Kernel($this->createConfiguration());

        return $this->kernel->getApp();
    }

    public function createConfiguration()
    {
        if (file_exists(__DIR__.'/../../config/local.php')) {
            $this->isLocal = true;
            $config = include __DIR__.'/../../config/local.php';
        } else {
            $this->isLocal = false;
            $config = include __DIR__.'/../../config/ci.php';
        }

        return $this->modifyConfiguration($config);
    }

    public function modifyConfiguration($config)
    {
        return $config;
    }
}
