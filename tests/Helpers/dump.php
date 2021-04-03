<?php

/**
 * CTOhm - SII Async Clients
 */

use Illuminate\Support\Facades\Storage;
use Kint\Kint;
use Kint\Parser\BlacklistPlugin;
use Kint\Renderer\CliRenderer;
use Kint\Renderer\RichRenderer;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tests\BaseTestCase;

\defined('STDERR') || \define('STDERR', \fopen('php://stderr', 'wb'));

if (!\function_exists('get_files')) {
    function get_files($files)
    {
        foreach ($files as $filename) {
            yield [$filename, \microtime(true), Storage::exists($filename)];
        }
    }
}
/**
 * ESta función sólo loguea a la consola o a un archivo.
 */
if (!\function_exists('kdump')) {
    function kdump(...$vars): void
    {
        $fp = \STDERR;

        CliRenderer::$cli_colors = true;
        $return = Kint::$return;
        Kint::$return = true;
        Kint::$enabled_mode = Kint::MODE_CLI;

        $kintdump = Kint::dump(...$vars);
        //dump($kintdump);
        \fwrite($fp, $kintdump);

        Kint::$return = $return;
    }

    Kint::$aliases[] = 'kdump';
}

if (!\function_exists('console')) {
    function console(): ConsoleOutput
    {
        return app(ConsoleOutput::class);
    }
}

if (!\function_exists('kdd')) {
    function kdd(...$args): void
    {
        kdump(...$args);
        /**
         * Avoid aborting the request when we're not in cli nor local.
         */
        exit();
    }

    Kint::$aliases[] = 'kdd';
}

RichRenderer::$folder = false;
BlacklistPlugin::$shallow_blacklist[] = 'Psr\Container\ContainerInterface';
Kint::$aliases[] = [BaseTestCase::class, 'kdump'];
Kint::$aliases[] = ["illuminate\support\collection", 'kdump'];
Kint::$aliases[] = 'dump';
Kint::$aliases[] = 'dd';
Kint::$aliases[] = 'd';

Kint::$aliases[] = ['tests\basetestcase', 'kdump'];
