<?php

$doc = <<<'HTML'
        <!DOCTYPE html>
        <body style="font-family: sans-serif; ">
            <div style="margin: 45px auto; max-width: 650px; background-color: #EEE; padding: 50px">
            <img src="https://avatars0.githubusercontent.com/u/1777367?v=3&s=100" height="45" style="float:left; margin: 0 10px"/>
            <h1 style="line-height: 45px; float:left; margin-top: 0">
                eLife Annotations API
            </h1>
            <div style="clear: both;"></div>
            %s
            </div>
        </body>
HTML;

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo sprintf($doc, '<p style="color: red">You must run composer install first before trying to install this.</p>');
    exit;
}

require_once __DIR__.'/bootstrap.php';
use eLife\Annotations\Kernel;

if (!file_exists(__DIR__.'/../config/local.php')) {
    $body = <<<'HTML'
        <p>To develop using the eLife Search API you need to set up a local configuration</p>
            <p>Please copy the example or add the following (including php start tag) to ./config/local.php</p>

            <pre style="margin: 0;">
                <code>
return [
    'debug' => true,
    'ttl' => 0,
];
                </code>
            </pre>
            <h2>Other requirements</h2>
            <ul>
                <li>eLife Sciences API</li>
                <li>PHP 7.1+</li>
            </ul>
HTML;
    echo sprintf($doc, $body);
} else {
    $config = include __DIR__.'/../config/local.php';
    // Start output buffer to catch anything unexpected.
    ob_start();
    // Wrap kernel.
    try {
        $kernel = new Kernel($config);

        $kernel->withApp(function ($app) use ($config) {
            $app['debug'] = $config['debug'] ?? false;
        });

        $kernel->run();
    } // Catch anything we can.
    catch (Throwable $t) {
        // Grab any printed warnings
        $content = ob_get_contents();
        // Clean output buffer to hide warnings.
        ob_clean();

        $output = '<p>'.$t->getMessage().'</p>'.($content ? '<h3>Warnings:</h3>'.'<p>'.$content.'</p>' : '');

        $output .= '<h4>Stack Trace</h4>'.$t->getTraceAsString();
        // Print error page.
        echo sprintf($doc, $output);
        // Flush back to user.
        ob_flush();
    }
}
