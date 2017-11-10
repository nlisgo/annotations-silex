<?php

namespace eLife\Annotations;

use Aws\Sqs\SqsClient;
use Closure;
use Doctrine\Common\Cache\FilesystemCache;
use eLife\Annotations\Api\AnnotationsController;
use eLife\Annotations\Provider\AnnotationsServiceProvider;
use eLife\ApiClient\HttpClient\BatchingHttpClient;
use eLife\ApiClient\HttpClient\Guzzle6HttpClient;
use eLife\ApiClient\HttpClient\NotifyingHttpClient;
use eLife\ApiSdk\ApiSdk;
use eLife\Bus\Limit\CompositeLimit;
use eLife\Bus\Limit\LoggingLimit;
use eLife\Bus\Limit\MemoryLimit;
use eLife\Bus\Limit\SignalsLimit;
use eLife\Bus\Queue\SqsMessageTransformer;
use eLife\Bus\Queue\SqsWatchableQueue;
use eLife\HypothesisClient\ApiSdk as HypothesisApiSdk;
use eLife\HypothesisClient\Credentials\Credentials;
use eLife\HypothesisClient\HttpClient\BatchingHttpClient as HypothesisBatchingHttpClient;
use eLife\HypothesisClient\HttpClient\Guzzle6HttpClient as HypothesisGuzzle6HttpClient;
use eLife\HypothesisClient\HttpClient\NotifyingHttpClient as HypothesisNotifyingHttpClient;
use eLife\Logging\LoggingFactory;
use eLife\Logging\Monitoring;
use eLife\Ping\Silex\PingControllerProvider;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Knp\Provider\ConsoleServiceProvider;
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
    const ROOT = __DIR__.'/../..';
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
            'ttl' => 300,
            'file_logs_path' => self::LOGS_DIR,
            'logging_level' => Logger::INFO,
            'api_url' => '',
            'api_requests_batch' => 10,
            'process_memory_limit' => 256,
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
            return new LoggingLimit(
                new CompositeLimit(
                    $app['limit._memory'],
                    $app['limit._signals']
                ),
                $app['logger']
            );
        };

        $app['limit.interactive'] = function (Application $app) {
            return new LoggingLimit(
                $app['limit._signals'],
                $app['logger']
            );
        };

        $app['hypothesis.guzzle'] = function (Application $app) {
            // Create default HandlerStack
            $stack = HandlerStack::create();
            $logger = $app['logger'];
            if ($app['config']['debug']) {
                $stack->push(
                    Middleware::mapRequest(function ($request) use ($logger) {
                        $logger->debug("Request performed in Guzzle Middleware: {$request->getUri()}");

                        return $request;
                    })
                );
            }

            return new Client([
                'base_uri' => $app['config']['hypothesis']['api_url'],
                'handler' => $stack,
            ]);
        };

        $app['hypthesis.sdk'] = function (Application $app) {
            $notifyingHttpClient = new HypothesisNotifyingHttpClient(
                new HypothesisBatchingHttpClient(
                    new HypothesisGuzzle6HttpClient(
                        $app['hypothesis.guzzle']
                    ),
                    $app['config']['api_requests_batch']
                )
            );
            if ($app['config']['debug']) {
                $logger = $app['logger'];
                $notifyingHttpClient->addRequestListener(function ($request) use ($logger) {
                    $logger->debug("Request performed in NotifyingHttpClient: {$request->getUri()}");
                });
            }

            $credentials = new Credentials(
                $app['config']['hypothesis']['client_id'],
                $app['config']['hypothesis']['secret_key'],
                $app['config']['hypothesis']['authority']
            );

            return new HypothesisApiSdk($notifyingHttpClient, $credentials);
        };

        $app['guzzle'] = function (Application $app) {
            // Create default HandlerStack
            $stack = HandlerStack::create();
            $logger = $app['logger'];
            if ($app['config']['debug']) {
                $stack->push(
                    Middleware::mapRequest(function ($request) use ($logger) {
                        $logger->debug("Request performed in Guzzle Middleware: {$request->getUri()}");

                        return $request;
                    })
                );
            }

            return new Client([
                'base_uri' => $app['config']['api_url'],
                'handler' => $stack,
            ]);
        };

        $app['api.sdk'] = function (Application $app) {
            $notifyingHttpClient = new NotifyingHttpClient(
                new BatchingHttpClient(
                    new Guzzle6HttpClient(
                        $app['guzzle']
                    ),
                    $app['config']['api_requests_batch']
                )
            );
            if ($app['config']['debug']) {
                $logger = $app['logger'];
                $notifyingHttpClient->addRequestListener(function ($request) use ($logger) {
                    $logger->debug("Request performed in NotifyingHttpClient: {$request->getUri()}");
                });
            }

            return new ApiSdk($notifyingHttpClient);
        };

        $app['aws.sqs'] = function (Application $app) {
            $config = [
                'version' => '2012-11-05',
                'region' => $app['config']['aws']['region'],
            ];
            if (isset($app['config']['aws']['endpoint'])) {
                $config['endpoint'] = $app['config']['aws']['endpoint'];
            }
            if (!isset($app['config']['aws']['credential_file']) || $app['config']['aws']['credential_file'] === false) {
                $config['credentials'] = [
                    'key' => $app['config']['aws']['key'],
                    'secret' => $app['config']['aws']['secret'],
                ];
            }

            return new SqsClient($config);
        };

        $app['aws.queue'] = function (Application $app) {
            return new SqsWatchableQueue($app['aws.sqs'], $app['config']['aws']['queue_name']);
        };

        $app['aws.queue_transformer'] = function (Application $app) {
            return new SqsMessageTransformer($app['api.sdk']);
        };

        $app['default_controller'] = function (Application $app) {
            return new AnnotationsController($app['logger'], $app['cache']);
        };

        $app->register(new ConsoleServiceProvider(), [
            'console.name' => 'Annotations console',
            'console.version' => '0.1.0',
            'console.project_directory' => self::ROOT,
        ]);

        $app->register(new AnnotationsServiceProvider(), [
            'annotations.hypothesis.sdk' => $app['hypthesis.sdk'],
            'annotations.limit.import' => $app->protect($app['limit.interactive']),
            'annotations.limit.watch' => $app->protect($app['limit.long_running']),
            'annotations.logger' => $app['logger'],
            'annotations.monitoring' => $app['monitoring'],
            'annotations.api.sdk' => $app['api.sdk'],
            'annotations.sqs' => $app['aws.sqs'],
            'annotations.sqs.queue' => $app['aws.queue'],
            'annotations.sqs.queue_message_type' => $app['config']['aws']['queue_message_default_type'],
            'annotations.sqs.queue_name' => $app['config']['aws']['queue_name'],
            'annotations.sqs.queue_transformer' => $app['aws.queue_transformer'],
            'annotations.sqs.region' => $app['config']['aws']['region'],
        ]);
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
