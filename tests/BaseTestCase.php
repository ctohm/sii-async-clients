<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests;

use CTOhm\SiiAsyncClients\Providers\SiiClientsProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Stringable;
use Orchestra\Testbench\Console\Kernel;
use Orchestra\Testbench\TestCase;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Tests\Helpers\SiiSignature;

/**
 * @internal
 * @coversNothing
 */
class BaseTestCase extends TestCase implements AfterLastTestHook, BeforeFirstTestHook
{
    /*
        |--------------------------------------------------------------------------
        | Bootstrap The Test Environment
        |--------------------------------------------------------------------------
        |
        | You may specify console commands that execute once before your test is
        | run. You are free to add your own additional commands or logic into
        | this file as needed in order to help your test suite run quicker.
        |
     */

    public function executeBeforeFirstTest(): void
    {
        $console = $this->app->make(Kernel::class);

        $commands = [
            'config:cache',
            'event:cache',
        ];

        foreach ($commands as $command) {
            $console->call($command);
        }
    }

    public function executeAfterLastTest(): void
    {
        \array_map('unlink', \glob('bootstrap/cache/*.phpunit.php'));
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->setBasePath(__DIR__ . '/../');

        $app->useStoragePath(__DIR__ . '/../storage');
        Storage::persistentFake('testing');

        $app->loadEnvironmentFrom($app->environmentFilePath());
        // Setup default database to use sqlite :memory:

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.local.root', storage_path());

        $app['config']->set('sii.certificado', (\file_get_contents(storage_path('cert.pfx'))));

        $app->singleton(SiiSignature::class, static function () {
            $pfxData = (object) [
                'content_b64' => config('sii.certificado'),
                'password_val' => encrypt(''),
            ];

            return new SiiSignature($pfxData);
        });
        $this->loadMacros();
    }

    protected function getPackageProviders($app)
    {
        return [SiiClientsProvider::class];
    }

    protected function loadMacros(): void
    {
        Stringable::macro('entitiesToChars', function () {
            // si no se paso un texto o bien es un número no se hace nada
            if (!\is_string($this->value)) {
                return new static($this->value);
            }

            // convertir "predefined entities" de XML
            $txt = \str_replace(
                ['&amp;', '&#38;', '&lt;', '&#60;', '&gt;', '&#62', '&quot;', '&#34;', '&apos;', '&#39;'],
                ['&', '&', '<', '<', '>', '>', '"', '"', '\'', '\''],
                $this->value
            );

            // entregar texto sanitizado
            return new static(\str_replace('&', '&amp;', $txt));
        });
        Stringable::macro('utf8ToIso', function () {
            // is it's already utf8, return itself
            if (\utf8_decode(\utf8_encode($this->value)) === $this->value) {
                return new static($this->value);
            }

            return new static(\utf8_decode($this->value));
        });

        Stringable::macro('isoToUTF8', function () {
            // is it's already utf8, return itself
            if (\utf8_encode(\utf8_decode($this->value)) === $this->value || true) {
                return new static($this->value);
            }

            return new static(\utf8_encode($this->value));
        });
    }
}
