<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients;

use CTOhm\SiiAsyncClients\RequestClients\Structures\RetrievesEventosHistoricosInterface;
use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use CTOhm\SiiAsyncClients\Util\ExceptionHelper;
use CTOhm\SiiAsyncClients\Wsdl\QueryEstDteAvanzadoClient;
use CTOhm\SiiAsyncClients\Wsdl\QueryEstDteClient;
use CTOhm\SiiAsyncClients\Wsdl\RegistroReclamoDteClient;
use CTOhm\SiiAsyncClients\Wsdl\RpetcWsdlClient;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\WsdlClientBase;
use CTOhm\SiiAsyncClients\Wsdl\TokenGetterClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Log;

/**
 * class to interact with SII.
 */
class SoapProvider implements RetrievesEventosHistoricosInterface
{
    /**
     * @var bool
     */
    public static $debug = true; // set to true to log soap requests

    public static array $defaultClientOptions = [
        'trace' => true,
        'exceptions' => true,
        'keep_alive' => false,
        'http_errors' => false,
    ];

    protected static SiiSignatureInterface $siiSignature;

    private static ?string $soapToken = null;

    private static ?TokenGetterClient $tokenGetterClient = null;

    private static ?QueryEstDteClient $queryEstDteClient = null;

    private static ?RpetcWsdlClient $rpetcWsdlClient = null;

    private static ?RegistroReclamoDteClient $registroReclamoDteClient = null;

    private static ?QueryEstDteAvanzadoClient $queryEstDteAvanzadoClient = null;

    /**
     * Undocumented function.
     *
     * @throws \Exception (description)
     */
    public function __construct(SiiSignatureInterface $siiSignature, array $options = [])
    {
        //$tokenReq = self::getTokenGetterClientInstance()->getTokenRequest();
        //dd($tokenReq);
        self::$siiSignature = $siiSignature;

        self::$defaultClientOptions = \array_merge(self::$defaultClientOptions, [
            'cache_wsdl' => config('sii-clients.cache_policy'),

            'delay' => config('sii-clients.default_request_delay_ms'),  // milliseconds * 1000 to deal in microseconds,

            WsdlClientBase::WSDL_CACHE_WSDL => config('sii-clients.cache_policy'),
        ], $options);
    }

    /**
     * Undocumented function.
     */
    public function consultarDocDteCedible(
        Structures\EventosHistoricosParameters $eventosHistoricosParameters
    ): PromiseInterface {
        $listarEventosHistDocParams = [
            'rutEmisor' => $eventosHistoricosParameters->rutEmisor,
            'dvEmisor' => $eventosHistoricosParameters->dvEmisor,
            'tipoDoc' => $eventosHistoricosParameters->tipoDoc,
            'folio' => $eventosHistoricosParameters->folio,
        ];
        //throw new \InvalidArgumentException(sprintf('Testing exception on %s',__METHOD__));

        $startTime = \microtime(true);

        return $this->getRegistroReclamoDteClientInstance(self::$defaultClientOptions)
            ->consultarDocDteCedibleAsync(...\array_values($listarEventosHistDocParams))
            ->then(static function ($result) {
                //kdump(['method' => 'consultarDocDteCedibleAsync','startTime' => $startTime,'compoetedTime' => microtime(true)                    ]);
                return (array) $result;
            })
            ->otherwise(static function ($e) {
                kdump([\get_class($e) => __METHOD__]);

                kdump(ExceptionHelper::normalizeException($e));

                return new FulfilledPromise(['error' => $e->getMessage()]);
            });
    }

    /**
     * Undocumented function.
     */
    public function listarEventosHistDoc(
        Structures\EventosHistoricosParameters $eventosHistoricosParameters
    ): PromiseInterface {
        $listarEventosHistDocParams = [
            'rutEmisor' => $eventosHistoricosParameters->rutEmisor,
            'dvEmisor' => $eventosHistoricosParameters->dvEmisor,
            'tipoDoc' => $eventosHistoricosParameters->tipoDoc,
            'folio' => $eventosHistoricosParameters->folio,
        ];

        $startTime = \microtime(true);

        return $this->getRegistroReclamoDteClientInstance(self::$defaultClientOptions)->listarEventosHistDocAsync(
            ...\array_values($listarEventosHistDocParams)
        )->then(static function ($result) {
            //kdump(['method' => 'listarEventosHistDocAsync','startTime' => $startTime,'compoetedTime' => microtime(true)                ]);

            return (new Structures\EventosHistoricosResponse((array) $result))->jsonSerialize();
        })->otherwise(static function ($e) {
            kdump([\get_class($e) => __METHOD__]);
            kdump(ExceptionHelper::normalizeException($e));

            return new FulfilledPromise(['error' => $e->getMessage()]);
        });
    }

    /**
     * Undocumented function.
     */
    public function getEstadoDte(
        Structures\EstadoDteParameters $estadoDteParameters
    ): PromiseInterface {
        $serializedParams = $estadoDteParameters->jsonSerialize();
        $soapMethodParams = \array_values($serializedParams);

        $startTime = \microtime(true);

        return $this->getQueryEstDteClientInstance(self::$defaultClientOptions)->getEstDte(...$soapMethodParams)->otherwise(static function ($e) {
            kdump(ExceptionHelper::normalizeException($e));

            return new FulfilledPromise(['error' => $e->getMessage()]);
        });
    }

    /**
     * Undocumented function.
     */
    public function getEstadoDteAv(
        Structures\EstadoDteAvParameters $estadoDteAvParameters
    ): PromiseInterface {
        $serializedParams = $estadoDteAvParameters->jsonSerialize();
        $soapMethodParams = \array_values($serializedParams);

        $startTime = \microtime(true);

        return $this->getQueryEstDteAvanzadoClientInstance(self::$defaultClientOptions)
            ->getEstDteAvAsync(...$soapMethodParams)
            ->otherwise(static function ($e) {
                dump(ExceptionHelper::normalizeException($e));
                Log::warning($e);

                return new FulfilledPromise(['error' => $e->getMessage()]);
            });
    }

    public function getEstadoCesion(
        Structures\EstadoCesionParameters $estadoCesionParameters
    ): PromiseInterface {
        return $this->getRpetcWsdlClientInstance(self::$defaultClientOptions)->getEstCesion(
            ...\array_values($estadoCesionParameters->jsonSerialize())
        )
            ->otherwise(static function ($e) {
                dump(ExceptionHelper::normalizeException($e));
                Log::warning($e);

                return new FulfilledPromise(['error' => $e->getMessage()]);
            });
    }

    public function getEstadoCesionRelacion(
        Structures\EstadoCesionRelacionParameters $estadoCesionRelacionParameters
    ): PromiseInterface {
        $startTime = \microtime(true);

        return $this->getRpetcWsdlClientInstance(self::$defaultClientOptions)->getEstCesionRelac(
            ...\array_values($estadoCesionRelacionParameters->jsonSerialize())
        )
            ->otherwise(static function ($e) {
                dump(ExceptionHelper::normalizeException($e));
                Log::warning($e);

                return new FulfilledPromise(['error' => $e->getMessage()]);
            });
    }

    /**
     * Undocumented function.
     *
     * @return string
     */
    public static function getToken(?SiiSignatureInterface $siiSignature = null)
    {
        $siiSignature = $siiSignature ?? self::$siiSignature;
        self::$soapToken = self::$soapToken ?? self::getTokenGetterClientInstance(self::$defaultClientOptions)->getCachedOrRenewedToken($siiSignature, self::$defaultClientOptions);

        $restClientOptions = ['cookies' => CookieJar::fromArray(['TOKEN' => self::$soapToken], 'sii.cl')];
        self::$defaultClientOptions['restClient'] = new RestClient($siiSignature, $restClientOptions);

        return self::$soapToken;
    }

    private static function getTokenGetterClientInstance(array $options = []): TokenGetterClient
    {
        if (!self::$tokenGetterClient) {
            self::$tokenGetterClient = new TokenGetterClient($options);
        }

        return self::$tokenGetterClient;
    }

    private static function getQueryEstDteClientInstance(array $options = []): QueryEstDteClient
    {
        $options['soapToken'] = $options['soapToken'] ?? self::getToken(self::$siiSignature);

        if (!self::$queryEstDteClient) {
            self::$queryEstDteClient = new QueryEstDteClient($options);
        }

        return self::$queryEstDteClient;
    }

    private static function getRpetcWsdlClientInstance(array $options = []): RpetcWsdlClient
    {
        $options['soapToken'] = $options['soapToken'] ?? self::getToken(self::$siiSignature);

        if (!self::$rpetcWsdlClient) {
            self::$rpetcWsdlClient = new RpetcWsdlClient($options);
        }

        return self::$rpetcWsdlClient;
    }

    private static function getRegistroReclamoDteClientInstance(array $options = []): RegistroReclamoDteClient
    {
        $options['soapToken'] = $options['soapToken'] ?? self::getToken(self::$siiSignature);

        if (!self::$registroReclamoDteClient) {
            self::$registroReclamoDteClient = new RegistroReclamoDteClient($options);
        }

        return self::$registroReclamoDteClient;
    }

    private static function getQueryEstDteAvanzadoClientInstance($options = []): QueryEstDteAvanzadoClient
    {
        $options['soapToken'] = $options['soapToken'] ?? self::getToken(self::$siiSignature);

        if (!self::$queryEstDteAvanzadoClient) {
            self::$queryEstDteAvanzadoClient = new QueryEstDteAvanzadoClient($options);
        }

        return self::$queryEstDteAvanzadoClient;
    }
}
