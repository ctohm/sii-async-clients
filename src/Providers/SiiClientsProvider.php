<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Providers;

use CTOhm\SiiAsyncClients\Commands\GenerateFromWsdlCommand;
use CTOhm\SiiAsyncClients\RequestClients\RestClient;
use CTOhm\SiiAsyncClients\RequestClients\RpetcClient;
use CTOhm\SiiAsyncClients\RequestClients\SoapProvider;
use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use Exception;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\CurlMultiHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

final class SiiClientsProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $config = config('sii-clients');
        \ini_set('soap.wsdl_cache_enabled', (string) (config('sii-clients.cache_policy') ? 1 : 0));
        \ini_set('soap.wsdl_cache_dir', (string) (config('sii-clients.cache_folder')));
        \ini_set('soap.wsdl_cache', (string) (config('sii-clients.cache_policy')));
        \ini_set('soap.wsdl_cache_limit', (string) (config('sii-clients.cache_policy') ? 10 : 0));

        $this->app->singleton(CurlMultiHandler::class, static function () {
            return new CurlMultiHandler([
                'options' => [
                    \CURLMOPT_MAX_TOTAL_CONNECTIONS => 24,
                    \CURLMOPT_MAX_HOST_CONNECTIONS => 6,
                ],
            ]);
        });
        $this->app->bind('command.wsdl2php:generate', GenerateFromWsdlCommand::class);

        $this->commands([
            'command.wsdl2php:generate',
        ]);

        $this->mergeConfigFrom(__DIR__ . '/../config/sii-clients.php', 'sii-clients');

        $this->app->singleton(SoapProvider::class, static function ($app, ?array $args = null): SoapProvider {
            $siiSignature = self::verifySiiSignatureParameter($args);

            $clientOptions = \array_merge([
                'retryAttempts' => 2,
                'debug' => config('sii-clients.soap_debug', false),
            ], Arr::except($args, ['siiSignature']));

            return new SoapProvider($siiSignature, $clientOptions);
        });

        $this->app->singleton(RestClient::class, static function ($app, ?array $args = null): RestClient {
            $siiSignature = self::verifySiiSignatureParameter($args);
            $clientOptions = Arr::except($args, ['siiSignature']);

            if (Cache::has('siiToken')) {
                $clientOptions['cookies'] = CookieJar::fromArray(Cache::get('siiToken'), 'sii.cl');
            }

            return new RestClient($siiSignature, $clientOptions);
        });

        $this->app->singleton(RpetcClient::class, static function ($app, ?array $args = null): RpetcClient {
            $siiSignature = self::verifySiiSignatureParameter($args);
            $clientOptions = Arr::except($args, ['siiSignature']);

            if (Cache::has('siiToken')) {
                $clientOptions['cookies'] = CookieJar::fromArray(Cache::get('siiToken'), 'sii.cl');
            }

            return new RpetcClient($siiSignature, $clientOptions);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/sii-clients.php' => config_path('sii-clients.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     *
     * @psalm-return array{0: string, 1: string, 2: string, 3: string}
     */
    public function provides()
    {
        return [
            CurlMultiHandler::class,
            RestClient::class,
            RpetcClient::class,
            SoapProvider::class,
        ];
    }

    private static function verifySiiSignatureParameter(?array $args): SiiSignatureInterface
    {
        if (!$siiSignature = $args['siiSignature'] ?? null) {
            throw new Exception('Debe llamarse a SoapProvider con "app()->makeWith([siiSignature=>$siiSignature])"');
        }

        if (!$siiSignature instanceof SiiSignatureInterface) {
            throw new InvalidArgumentException('El parámetro siiSignature debe implementar SiiSignatureInterface');
        }

        return $siiSignature;
    }
}
