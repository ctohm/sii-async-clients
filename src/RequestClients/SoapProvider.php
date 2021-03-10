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
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
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

    public static $defaultClientOptions = [
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
    ): array {
        $listarEventosHistDocParams = [
            'rutEmisor' => $eventosHistoricosParameters->rutEmisor,
            'dvEmisor' => $eventosHistoricosParameters->dvEmisor,
            'tipoDoc' => $eventosHistoricosParameters->tipoDoc,
            'folio' => $eventosHistoricosParameters->folio,
        ];
        //throw new \InvalidArgumentException(sprintf('Testing exception on %s',__METHOD__));
        try {
            $startTime = \microtime(true);

            return $this->getRegistroReclamoDteClientInstance()
                ->consultarDocDteCedibleAsync(...\array_values($listarEventosHistDocParams))
                ->then(static function ($result) {
                    //kdump(['method' => 'consultarDocDteCedibleAsync','startTime' => $startTime,'compoetedTime' => microtime(true)                    ]);
                    return (array) $result;
                })
                ->otherwise(static function ($err) {
                    kdump(ExceptionHelper::normalizeException($err));

                    return [];
                })
                ->wait();
        } catch (\Exception $e) {
            dump(ExceptionHelper::normalizeException($e));

            $logger = debuglog(); //->warn($e);
            kdump($logger);

            return new Promise(static fn () => ['error' => $e->getMessage()]);
        }
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

        try {
            $startTime = \microtime(true);

            return $this->getRegistroReclamoDteClientInstance()->listarEventosHistDocAsync(
                ...\array_values($listarEventosHistDocParams)
            )->then(static function ($result) {
                //kdump(['method' => 'listarEventosHistDocAsync','startTime' => $startTime,'compoetedTime' => microtime(true)                ]);

                return (new Structures\EventosHistoricosResponse((array) $result))->jsonSerialize();
            })->otherwise(static function ($err) {
                kdump(ExceptionHelper::normalizeException($err));

                return [];
            });
        } catch (\Exception $e) {
            dump(ExceptionHelper::normalizeException($e));
            Log::warning($e);

            return new FulfilledPromise(['error' => $e->getMessage()]);
        }
    }

    /**
     * Undocumented function.
     */
    public function getEstadoDte(
        Structures\EstadoDteParameters $estadoDteParameters
    ): PromiseInterface {
        $serializedParams = $estadoDteParameters->jsonSerialize();
        $soapMethodParams = \array_values($serializedParams);

        try {
            $startTime = \microtime(true);

            return $this->getQueryEstDteClientInstance()->getEstDteAsync(...$soapMethodParams)->then(
                static function (array $parsedResponse) {
                    return $parsedResponse;
                }
            )
                ->then(static function ($result) {
                    //kdump(['method' => 'getEstDteAsync','startTime' => $startTime,'compoetedTime' => microtime(true)                    ]);
                    return $result;
                })->otherwise(static function ($err) {
                    kdump(ExceptionHelper::normalizeException($err));

                    return [];
                });
        } catch (\Exception $e) {
            dump(ExceptionHelper::normalizeException($e));
            Log::warning($e);

            return new Promise(static fn () => ['error' => $e->getMessage()]);
        }
    }

    /**
     * Undocumented function.
     */
    public function getEstadoDteAv(
        Structures\EstadoDteAvParameters $estadoDteAvParameters
    ): PromiseInterface {
        $serializedParams = $estadoDteAvParameters->jsonSerialize();
        $soapMethodParams = \array_values($serializedParams);

        try {
            $startTime = \microtime(true);

            return $this->getQueryEstDteAvanzadoClientInstance()
                ->getEstDteAvAsync(...$soapMethodParams)
                ->then(static function ($result) {
                    //kdump(['method' => 'getEstDteAvAsync','startTime' => $startTime,'compoetedTime' => microtime(true)                    ]);
                    return $result;
                })->otherwise(static function ($e) {
                    dump(ExceptionHelper::normalizeException($e));
                    Log::warning($e);

                    return new Promise(static fn () => ['error' => $e->getMessage()]);
                });
        } catch (\Exception $e) {
            dump(ExceptionHelper::normalizeException($e));
            Log::warning($e);

            return new Promise(static fn () => ['error' => $e->getMessage()]);
        }
    }

    public function getEstadoCesion(
        Structures\EstadoCesionParameters $estadoCesionParameters
    ): PromiseInterface {
        try {
            $rpetcClient = $this->getRpetcWsdlClientInstance();

            $startTime = \microtime(true);

            return $rpetcClient->getEstCesion(
                ...\array_values($estadoCesionParameters->jsonSerialize())
            )
                ->then(static function ($result) {
                    //kdump(['method' => 'getEstCesion','startTime' => $startTime,'compoetedTime' => microtime(true)                    ]);
                    return $result;
                })
                ->otherwise(static function ($e) {
                    dump(ExceptionHelper::normalizeException($e));
                    Log::warning($e);

                    return new Promise(static fn () => ['error' => $e->getMessage()]);
                });
        } catch (\Exception $e) {
            dump(ExceptionHelper::normalizeException($e));
            Log::warning($e);

            return new Promise(static fn () => ['error' => $e->getMessage()]);
        }
    }

    public function getEstadoCesionRelacion(
        Structures\EstadoCesionRelacionParameters $estadoCesionRelacionParameters
    ): PromiseInterface {
        try {
            $rpetcClient = $this->getRpetcWsdlClientInstance();

            $startTime = \microtime(true);

            return $rpetcClient->getEstCesionRelac(
                ...\array_values($estadoCesionRelacionParameters->jsonSerialize())
            )
                ->then(static function ($result) {
                    //kdump(['method' => 'getEstCesionRelac','startTime' => $startTime,'compoetedTime' => microtime(true)                    ]);
                    return $result;
                })

                ->otherwise(static function ($e) {
                    dump(ExceptionHelper::normalizeException($e));
                    Log::warning($e);

                    return new Promise(static fn () => ['error' => $e->getMessage()]);
                });
        } catch (\Exception $e) {
            dump(ExceptionHelper::normalizeException($e));
            Log::warning($e);

            return new Promise(static fn () => ['error' => $e->getMessage()]);
        }
    }

    /**
     * Undocumented function.
     *
     * @return string
     */
    public static function getToken(?SiiSignatureInterface $siiSignature = null)
    {
        $siiSignature = $siiSignature ?? self::$siiSignature;
        self::$soapToken = self::$soapToken ?? self::getTokenGetterClientInstance()->getCachedOrRenewedToken($siiSignature, self::$defaultClientOptions);

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
