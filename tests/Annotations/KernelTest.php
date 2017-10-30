<?php

namespace tests\eLife\Annotations;

use eLife\Annotations\Kernel;
use Silex\WebTestCase;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class KernelTest extends WebTestCase
{
    public function createApplication() : HttpKernelInterface
    {
        return Kernel::create();
    }

    /**
     * @test
     */
    public function testPing()
    {
        $client = $this->createClient();
        $client->request('GET', '/ping');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('pong', $response->getContent());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
    }
}
