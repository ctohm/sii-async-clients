<?php

/**
 * CTOhm - SII Async Clients
 */

use Kint\Kint;
use Kint\Parser\BlacklistPlugin;
use Kint\Renderer\CliRenderer;
use Kint\Renderer\RichRenderer;
use Symfony\Component\Console\Output\ConsoleOutput;

\defined('STDERR') || \define('STDERR', \fopen('php://stderr', 'wb'));

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
    Kint::$aliases[] = 'kdiffd';
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

Kint::$aliases[] = 'dump';
Kint::$aliases[] = 'dd';
Kint::$aliases[] = 'd';
