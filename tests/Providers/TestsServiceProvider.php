<?php

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Support\ServiceProvider;
use Tests\Commands\GenerateFromWsdlCommand;

class TestsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $config = $this->app['config'];
        $config->set('serializer.debug', true);
        $config->set('serializer.cache_dir', storage_path('framework/cache'));
        $config->set('serializer.resource_folder', base_path('src/resources'));
        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->app->bind('command.wsdl2php:generate', GenerateFromWsdlCommand::class);
        $this->commands(['command.wsdl2php:generate']);
        //kdump($this->app['files']);
        //$this->app->when(IdeHelperServiceProvider::class)->needs('files')->give(null);

        //$this->app->register(IdeHelperServiceProvider::class);
    }
}
