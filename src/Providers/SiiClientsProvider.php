<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Providers;

use CTOhm\SiiAsyncClients\Commands\GenerateFromWsdlCommand;
use CTOhm\SiiAsyncClients\RequestClients\DteWsClient;
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
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use InvalidArgumentException;

final class SiiClientsProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        \ini_set('soap.wsdl_cache_enabled', (string) (config('sii-clients.cache_policy') ? \WSDL_CACHE_BOTH : 0));
        \ini_set('soap.wsdl_cache_dir', (string) (config('sii-clients.cache_folder')));
        \ini_set('soap.wsdl_cache', (string) (config('sii-clients.cache_policy')));
        \ini_set('soap.wsdl_cache_limit', (string) (config('sii-clients.cache_policy') ? 10 : 0));

        $this->mergeConfigFrom(__DIR__ . '/../config/sii-clients.php', 'sii-clients');

        $this->app->singleton(CurlMultiHandler::class, static function () {
            return new CurlMultiHandler([
                'options' => [
                    \CURLMOPT_MAX_TOTAL_CONNECTIONS => 24,
                    \CURLMOPT_MAX_HOST_CONNECTIONS => 6,
                ],
            ]);
        });
        //        $this->app->bind('command.wsdl2php:generate', GenerateFromWsdlCommand::class);

        //$this->commands(['command.wsdl2php:generate',]);

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
        $this->app->singleton(DteWsClient::class, static function ($app, ?array $args = null): DteWsClient {
            $siiSignature = self::verifySiiSignatureParameter($args);
            $clientOptions = Arr::except($args, ['siiSignature']);

            if (Cache::has('siiToken')) {
                $clientOptions['cookies'] = CookieJar::fromArray(Cache::get('siiToken'), 'sii.cl');
            }

            return new DteWsClient($siiSignature, $clientOptions);
        });
        Stringable::macro('detectEncoding', function (): string {
            /** @var Stringable $stringable */
            $stringable = $this;

            return Str::detectEncoding($stringable->value);
        });
        Str::macro('detectEncoding', static function (string $value): string {
            return \mb_detect_encoding($value, [
                'HTML-ENTITIES',     'WINDOWS-1252', 'us-ascii', 'ASCII', 'UTF-8', 'ISO-8859-1',
            ], true);
        });

        /*
         * * example: betweenInclusive('<person><name  id="1" >ctohm</name></person>',   '<name','</person>') returns '<name  id="1" >ctohm</name></person>'
         */
        Stringable::macro('betweenInclusive', function (string $from, string $to): Stringable {
            /** @var Stringable $stringable */
            $stringable = $this;

            if (!$stringable->containsAll([$from, $to])) {
                //kdd(['string does not contain both ' => [$from, $to, $stringable->__toString()]]);
                return $stringable;
            }

            return $stringable->after($from)->beforeLast($to)->prepend($from)->append($to);
        });
        Str::macro('betweenInclusive', static function (string $value, string $from, string $to): Stringable {
            return Str::of($value)->betweenInclusive($from, $to);
        });
        Stringable::macro('binToHex', function (): Stringable {
            /** @var Stringable $stringable */
            $stringable = $this;

            return Str::of(\bin2hex($stringable->__toString()));
        });
        Stringable::macro('hexToBin', function (): Stringable {
            /** @var Stringable $stringable */
            $stringable = $this;

            return Str::of(\hex2bin($stringable->__toString()));
        });
        /*
         *
         *
         *
         * *example: betweenTags('<person><name id="1" >ctohm</name></person>', 'name') returns '<name  id="1" >ctohm</name>'
         */
        Stringable::macro('betweenTags', function (string $tag): Stringable {
            /** @var Stringable $stringable */
            $stringable = $this;

            $startingTag = $stringable->match('/<' . $tag . '[\s>]/');

            return $stringable->binToHex()
                ->betweenInclusive(
                    $startingTag->binToHex()->__toString(),
                    \bin2hex(\sprintf('</%s>', $tag))
                )->hexToBin();

            return Str::of(\hex2bin(Str::of(\bin2hex($stringable->__toString()))
                ->betweenInclusive(
                    \bin2hex($startingTag->__toString()),
                    \bin2hex(\sprintf('</%s>', $tag))
                )->__toString()));
        });
        Str::macro('betweenTags', static function (string $value, string $tag): Stringable {
            return Str::of($value)->betweenTags($tag);
        });
        Stringable::macro('base64Encode', function (): Stringable {
            /** @var Stringable $stringable */
            $stringable = $this;

            return Str::of(\base64_encode($stringable->value));
        });
        Stringable::macro('base64Decode', function (): Stringable {
            /** @var Stringable $stringable */
            $stringable = $this;

            return Str::of(\base64_decode($stringable->value, true));
        });
        Stringable::macro('sha1', function (): Stringable {
            /** @var Stringable $stringable */
            $stringable = $this;

            return Str::of(\sha1($stringable->value, true));
        });

        /*
         *
         *
         * * example: insideTags('<person><name  id="1" >ctohm</name></person>', 'name') returns 'ctohm'
         */
        Stringable::macro('insideTags', function (string $tag): Stringable {
            /** @var Stringable $stringable */
            $stringable = $this;

            $startingTag = $stringable->match('/<' . $tag . '[\s>]/');

            return $stringable->after($startingTag)->before(\sprintf('</%s>', $tag))->between(
                \sprintf('>', $tag),
                \sprintf('</', $tag)
            );
        });
        Str::macro('insideTags', static function (string $value, string $tag): Stringable {
            return Str::of($value)->insideTags($tag);
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
