<?php

namespace eLife\Annotations;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

final class Kernel
{
    const ROOT = __DIR__.'/../../..';

    public static function create() : Application
    {
        // Create application.
        $app = new Application();
        // Routes
        self::routes($app);

        return $app;
    }

    /**
     * @SuppressWarnings(PHPMD.ForbiddenDateTime)
     */
    public static function routes(Application $app)
    {
        // Routes.
        $app->get(
            '/ping', function () {
                return new Response(
                    'pong',
                    200,
                    [
                        'Cache-Control' => 'must-revalidate, no-cache, no-store, private',
                        'Content-Type' => 'text/plain; charset=UTF-8',
                    ]
                );
            }
        );
    }
}
