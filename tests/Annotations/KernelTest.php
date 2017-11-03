<?php

namespace tests\eLife\Annotations;

use eLife\Annotations\Kernel;
use Silex\WebTestCase;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class KernelTest extends WebTestCase
{
    public function createApplication() : HttpKernelInterface
    {
        return (new Kernel())->getApp();
    }

    /**
     * @test
     */
    public function it_returns_200_pong_when_the_application_is_correctly_setup()
    {
        $client = static::createClient();

        $client->request('GET', '/ping');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $client->getResponse()->headers->get('Content-Type'));
        $this->assertSame('pong', $client->getResponse()->getContent());
        $this->assertFalse($client->getResponse()->isCacheable());
    }
}
