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
final class RpetcWsdlClient extends WsdlClientBase
{
    /**
     * Minimal options.
     *
     *  @var (string|string[])[]
     */
    protected static $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://palena.sii.cl/DTEWS/services/wsRPETCConsulta?WSDL',
        WsdlClientBase::WSDL_CLASSMAP => [self::class],        WsdlClientBase::LOCAL_FILE => 'wsdl/palena/wsRPETCConsulta.jws',
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
        try {
            return $this->getAsyncSoapClient()->getEstCesion(
                $token,
                $rutEmisor,
                $dVEmisor,
                $tipoDoc,
                $folioDoc/*, $idCesion*/
            )
                ->then(fn ($result) => $this->parseConsultaRTC($result));
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return false;
        }
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
     *
     * @return
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
        try {
            return $this->getAsyncSoapClient()->getEstCesionRelac(
                $token,
                $rutEmisor,
                $dVEmisor,
                $tipoDoc,
                $folioDoc,
                $rutEmpresa,
                $dVEmpresa
            )
                ->then(fn ($result) => $this->parseConsultaRTC($result));
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return false;
        }
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
     *
     * @return false|string
     */
    public function getEstEnvio(
        $token,
        $trackId
    ) {
        try {
            $this->setResult($this->getSoapClient()->getEstEnvio($token, $trackId));

            return $this->getResult();
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return false;
        }
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
