<?php

namespace eLife\Annotations\Api;

use Doctrine\Common\Cache\Cache;
use Psr\Log\LoggerInterface;

final class AnnotationsController
{
    private $cache;
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        Cache $cache
    ) {
        $this->logger = $logger;
        $this->cache = $cache;
    }
}
