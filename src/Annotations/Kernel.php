<?php

namespace eLife\Annotations;

use Closure;
use Doctrine\Common\Cache\FilesystemCache;
use eLife\Annotations\Api\AnnotationsController;
use eLife\Bus\Limit\CompositeLimit;
use eLife\Bus\Limit\LoggingMiddleware;
use eLife\Bus\Limit\MemoryLimit;
use eLife\Bus\Limit\SignalsLimit;
use eLife\Logging\LoggingFactory;
use eLife\Logging\Monitoring;
use eLife\Ping\Silex\PingControllerProvider;
use Monolog\Logger;
use Silex\Application;
use Silex\Provider;
use Silex\Provider\VarDumperServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Kernel implements MinimalKernel
{
    const CACHE_DIR = __DIR__.'/../../var/cache';
    const LOGS_DIR = __DIR__.'/../../var/logs';

    public static $routes = [];

    private $app;

    public function __construct($config = [])
    {
        $app = new Application();
        // Load config
        $app['config'] = array_merge([
            'debug' => false,
            'ttl' => 0,
            'file_logs_path' => self::LOGS_DIR,
            'logging_level' => Logger::INFO,
        ], $config);
        $app->register(new PingControllerProvider());
        if ($app['config']['debug']) {
            $app->register(new VarDumperServiceProvider());
            $app->register(new Provider\HttpFragmentServiceProvider());
            $app->register(new Provider\ServiceControllerServiceProvider());
            $app->register(new Provider\TwigServiceProvider());
            $app->register(new Provider\WebProfilerServiceProvider(), [
                'profiler.cache_dir' => self::CACHE_DIR.'/profiler',
                'profiler.mount_prefix' => '/_profiler', // this is the default
            ]);
        }
        // DI.
        $this->dependencies($app);
        // Add to class once set up.
        $this->app = $this->applicationFlow($app);
    }

    public function dependencies(Application $app)
    {

        // General cache.
        $app['cache'] = function () {
            return new FilesystemCache(self::CACHE_DIR);
        };

        $app['logger'] = function (Application $app) {
            $factory = new LoggingFactory($app['config']['file_logs_path'], 'annotations-api', $app['config']['logging_level']);

            return $factory->logger();
        };

        $app['monitoring'] = function (Application $app) {
            return new Monitoring();
        };

        /*
         * @internal
         */
        $app['limit._memory'] = function (Application $app) {
            return MemoryLimit::mb($app['config']['process_memory_limit']);
        };
        /*
         * @internal
         */
        $app['limit._signals'] = function (Application $app) {
            return SignalsLimit::stopOn(['SIGINT', 'SIGTERM', 'SIGHUP']);
        };

        $app['limit.long_running'] = function (Application $app) {
            return new LoggingMiddleware(
                new CompositeLimit(
                    $app['limit._memory'],
                    $app['limit._signals']
                ),
                $app['logger']
            );
        };

        $app['limit.interactive'] = function (Application $app) {
            return new LoggingMiddleware(
                $app['limit._signals'],
                $app['logger']
            );
        };

        $app['default_controller'] = function (Application $app) {
            return new AnnotationsController($app['logger'], $app['cache']);
        };
    }

    public function applicationFlow(Application $app) : Application
    {
        // Routes
        $this->routes($app);
        // Cache.
        if ($app['config']['ttl'] > 0) {
            $app->after([$this, 'cache'], 3);
        }
        // Error handling.
        if (!$app['config']['debug']) {
            $app->error([$this, 'handleException']);
        }

        // Return
        return $app;
    }

    public static function routes(Application $app)
    {
        foreach (self::$routes as $route => $action) {
            $app->get($route, [$app['default_controller'], $action]);
        }
    }

    public function run()
    {
        return $this->app->run();
    }

    public function get($d)
    {
        return $this->app[$d];
    }

    public function getApp() : Application
    {
        return $this->app;
    }

    public function handleException(Throwable $e) : Response
    {
        return new JsonResponse([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    public function withApp(callable $fn, $scope = null) : Kernel
    {
        $boundFn = Closure::bind($fn, $scope ? $scope : $this);
        $boundFn($this->app);

        return $this;
    }

    public function cache(Request $request, Response $response) : Response
    {
        $response->setMaxAge($this->app['config']['ttl']);
        $response->headers->addCacheControlDirective('stale-while-revalidate', $this->app['config']['ttl']);
        $response->headers->addCacheControlDirective('stale-if-error', 86400);
        $response->setVary('Accept');
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
