<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\QueryEstDteAsyncClient;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\WsdlClientBase;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * This class stands for Get ServiceType.
 *
 * @template T2
 * @template-extends WsdlClientBase<QueryEstDteAsyncClient>
 *
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients
 */
class QueryEstDteClient extends WsdlClientBase
{
    public const WSDL_SLUG = 'query_est_dte';

    /**
     * Undocumented variable.
     *
     * @psalm-var  T2
     *
     * @var \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\QueryEstDteAsyncClient
     */
    protected static $asyncSoapClient = null;

    /**
     *  Minimal options.
     *
     * @var (string|string[])[]
     */
    protected static $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://palena.sii.cl/DTEWS/QueryEstDte.jws?WSDL',
        WsdlClientBase::LOCAL_FILE => __DIR__ . '/../resources/wsdl/QueryEstDte.jws',
        WsdlClientBase::WSDL_CLASSMAP => [self::class],
    ];

    protected array $mergedClientOptions = [];

    public function __construct(array $clientOptions = [])
    {
        self::$clientOptions[WsdlClientBase::LOCAL_FILE] = config(\sprintf('sii-clients.%s', self::WSDL_SLUG), self::$clientOptions[WsdlClientBase::LOCAL_FILE]);
        $this->mergedClientOptions = \array_merge(self::$clientOptions, $clientOptions);
        parent::__construct($this->mergedClientOptions);

        if ($clientOptions['soapToken'] ?? null) {
            $this->setToken($clientOptions['soapToken']);
        }
    }

    /**
     * Method to call the operation originally named getEstDte.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string[] ...$args
     */
    public function getEstDte(
        ...$args
    ): PromiseInterface {
        return $this->getEstDteAsync(
            ...$args
        )
            ->otherwise(function ($err) use ($args) {
                \usleep(500000);
                // retry once
                return $this->getEstDteAsync(
                    ...$args
                );
            });
    }

    /**
     * Method to call the operation originally named getEstDte.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $rutConsultante
     * @param string $dvConsultante
     * @param string $rutCompania
     * @param string $dvCompania
     * @param string $rutReceptor
     * @param string $dvReceptor
     * @param string $tipoDte
     * @param string $folioDte
     * @param string $fechaEmisionDte
     * @param string $montoDte
     * @param string $token
     */
    public function getEstDteAsync(
        $rutConsultante,
        $dvConsultante,
        $rutCompania,
        $dvCompania,
        $rutReceptor,
        $dvReceptor,
        $tipoDte,
        $folioDte,
        $fechaEmisionDte,
        $montoDte,
        $token
    ): PromiseInterface {
        return $this->getAsyncSoapClient(QueryEstDteAsyncClient::class)->getEstDte(
            $rutConsultante,
            $dvConsultante,
            $rutCompania,
            $dvCompania,
            $rutReceptor,
            $dvReceptor,
            $tipoDte,
            $folioDte,
            $fechaEmisionDte,
            $montoDte,
            $token
        )
            ->then(function (
                string $getEstDteRespuesta
            ) {
                return tap(self::parseSIIRespuesta($getEstDteRespuesta), fn ($result) => $this->setResult($result));
            });
    }

    public static function parseSIIRespuesta(string $getEstDteRespuesta)
    {
        $siiRespuesta = collect([\LSS\XML2Array::createArray($getEstDteRespuesta)])
            ->pluck('SII:RESPUESTA')->first();
        $result = collect($siiRespuesta)->reduce(
            static function (
                Collection $accum,
                array $item
            ): Collection {
                $fixed = collect($item)->keys()->map(static fn ($key) => Str::camel(\mb_strtolower($key)))->combine(\array_values($item));

                return $accum->merge($fixed);
            },
            collect([])
        );

        return $result->all();
    }
}
