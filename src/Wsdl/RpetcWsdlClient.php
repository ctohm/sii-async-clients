<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\RpetcWsdlAsyncClient;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\WsdlClientBase;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Collection;

/**
 * This class stands for Get ServiceType.
 *
 *
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients
 */
final class RpetcWsdlClient extends WsdlClientBase
{
    public const WSDL_SLUG = 'ws_rpetc_consulta';

    /**
     * Undocumented variable.
     *
     * @psalm-var \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient<\CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\RpetcWsdlAsyncClient>
     *
     * @var  \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient<\CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\RpetcWsdlAsyncClient>
     */
    protected static $asyncSoapClient = null;

    /**
     * Minimal options.
     *
     *  @var (string|string[])[]
     */
    protected static $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://palena.sii.cl/DTEWS/services/wsRPETCConsulta?WSDL',
        WsdlClientBase::LOCAL_FILE => __DIR__ . '/../resources/wsdl/wsRPETCConsulta.jws',
        WsdlClientBase::WSDL_CLASSMAP => [self::class],
    ];

    protected array $mergedClientOptions = [];

    public function __construct(array $clientOptions = [])
    {
        self::$clientOptions[WsdlClientBase::LOCAL_FILE] = config(\sprintf('sii-clients.%s', self::WSDL_SLUG), self::$clientOptions[WsdlClientBase::LOCAL_FILE]);

        $this->mergedClientOptions = \array_merge(self::$clientOptions, $clientOptions);
        parent::__construct($this->mergedClientOptions);

        if ($clientOptions['soapToken'] ?? null) {
            $clientOptions['classmap'] = self::$clientOptions[WsdlClientBase::WSDL_CLASSMAP];

            $this->setToken($clientOptions['soapToken']);
        }
    }

    /**
     * Method to call the operation originally named getEstCesion.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $token
     * @param string $rutEmisor
     * @param string $dVEmisor
     * @param string $tipoDoc
     * @param string $folioDoc
     * @/param string $idCesion
     */
    public function getEstCesion(
        $token,
        $rutEmisor,
        $dVEmisor,
        $tipoDoc,
        $folioDoc/*, $idCesion*/
    ): PromiseInterface {
        return $this->getAsyncSoapClient(RpetcWsdlAsyncClient::class)->getEstCesion(
            $token,
            $rutEmisor,
            $dVEmisor,
            $tipoDoc,
            $folioDoc/*, $idCesion*/
        )
            ->then(fn ($result) => $this->parseConsultaRTC($result))->otherwise(function ($soapFault) {
                return tap(false, fn () => $this->saveLastError('getEstDte', $soapFault));
            });
    }

    /**
     * Method to call the operation originally named getEstCesionRelac.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $token
     * @param string $rutEmisor
     * @param string $dVEmisor
     * @param string $tipoDoc
     * @param string $folioDoc
     * @param string $rutEmpresa
     * @param string $dVEmpresa
     */
    public function getEstCesionRelac(
        $token,
        $rutEmisor,
        $dVEmisor,
        $tipoDoc,
        $folioDoc,
        $rutEmpresa,
        $dVEmpresa
    ): PromiseInterface {
        return $this->getAsyncSoapClient(RpetcWsdlAsyncClient::class)->getEstCesionRelac(
            $token,
            $rutEmisor,
            $dVEmisor,
            $tipoDoc,
            $folioDoc,
            $rutEmpresa,
            $dVEmpresa
        )
            ->then(fn ($result) => $this->parseConsultaRTC($result))
            ->otherwise(function ($soapFault) {
                return tap(false, fn () => $this->saveLastError('getEstDte', $soapFault));
            });
    }

    /**
     * Method to call the operation originally named getEstEnvio.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $token
     * @param string $trackId
     */
    public function getEstEnvio(
        $token,
        $trackId
    ): PromiseInterface {
        return $this->getAsyncSoapClient(RpetcWsdlAsyncClient::class)->getEstEnvio($token, $trackId)
            ->then(function ($result) {
                return tap($this->parseConsultaRTC($result), fn ($result) => $this->setResult($result));
            })
            ->otherwise(function ($soapFault) {
                return tap(false, fn () => $this->saveLastError('getEstDte', $soapFault));
            });
    }

    /**
     * Undocumented function.
     */
    public static function simpleXMLNoCData(string $body): \SimpleXMLElement
    {
        return new \SimpleXMLElement($body, \LIBXML_COMPACT);
    }

    public function parseConsultaRTC(string $body): array
    {
        $siiRespuesta = collect(\LSS\XML2Array::createArray($body))->get('SII:RESPUESTA');
        $siiRespuesta = collect($siiRespuesta)->only(['SII:RESP_HDR', 'SII:RESP_BODY'])
            ->reduceWithKeys(static function (Collection $accum, $values, string $key) {
                return $accum->merge(\is_array($values) ? $values : [$key => $values]);
            }, collect([]));

        return $siiRespuesta->keys()->map(static fn ($key) => \str_replace('sii:', '', \mb_strtolower((string) $key)))->combine($siiRespuesta->values())->all();
    }
}
