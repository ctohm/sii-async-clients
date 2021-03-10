<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\WsdlClientBase;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Collection;

/**
 * This class stands for Get ServiceType.
 *
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients
 */
class QueryEstDteClient extends WsdlClientBase
{
    /**
     *  Minimal options.
     *
     * @var (string|string[])[]
     */
    protected static $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://palena.sii.cl/DTEWS/QueryEstDte.jws?WSDL',
        WsdlClientBase::LOCAL_FILE => 'wsdl/palena/QueryEstDte.jws',
        WsdlClientBase::WSDL_CLASSMAP => [self::class],
    ];

    protected array $mergedClientOptions = [];

    private static ?AsyncSoapClient $asyncSoapClient = null;

    public function __construct(array $clientOptions = [])
    {
        $this->mergedClientOptions = \array_merge(self::$clientOptions, $clientOptions);
        parent::__construct($this->mergedClientOptions);

        if ($clientOptions['soapToken'] ?? null) {
            $this->setToken($clientOptions['soapToken']);
            $this->getAsyncSoapClient();
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
     *
     * @return bool|string
     */
    public function getEstDte(
        ...$args
    ) {
        return $this->getEstDteAsync(
            ...$args
        )
            ->then(function ($result) {
                $this->setResult($result);

                return $result;
            })
            ->wait();
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
        return $this->getAsyncSoapClient()->getEstDte(
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
            ->then(static function (
                string $getEstDteRespuesta
            ) {
                return self::parseSIIRespuesta($getEstDteRespuesta);
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
                return $accum->merge(\array_change_key_case($item))->siiKeysToCamelCase()->except(['siiRESPHDR', 'SII:RESP_HDR']);
            },
            collect([])
        );

        return $result->all();
    }
}
