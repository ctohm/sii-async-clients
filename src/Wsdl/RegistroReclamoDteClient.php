<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\WsdlClientBase;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * This class stands for Listar ServiceType.
 *
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients
 */
final class RegistroReclamoDteClient extends WsdlClientBase
{
    /**
     * @var string
     */

    /**
     * Minimal options.
     */
    protected static array $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://ws1.sii.cl/WSREGISTRORECLAMODTE/registroreclamodteservice?wsdl',
        WsdlClientBase::LOCAL_FILE => 'wsdl/palena/registroreclamodteservice.jws',

        WsdlClientBase::WSDL_CLASSMAP => [
            'respuestaTo' => \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\RespuestaTo::class,
            'DteEventoDocTo' => \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo::class,
            'Exception' => \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\Exception::class,
        ],
    ];

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
     * Method to call the operation originally named consultarDocDteCedible.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @return false|string|WsRegistroReclamoDte\RespuestaTo
     */
    public function consultarDocDteCedible(...$args)
    {
        try {
            $this->setResult($this->consultarDocDteCedibleAsync(...$args));

            return $this->getResult();
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return false;
        }
    }

    /**
     * Method to call the operation originally named consultarDocDteCedible.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $rutEmisor
     * @param string $dvEmisor
     * @param string $tipoDoc
     * @param string $folio
     */
    public function consultarDocDteCedibleAsync($rutEmisor, $dvEmisor, $tipoDoc, $folio): PromiseInterface
    {
        try {
            return $this->getAsyncSoapClient()->consultarDocDteCedible(
                $rutEmisor,
                $dvEmisor,
                $tipoDoc,
                $folio
            )->then(function (
                $consultarDocDteCedibleRespuesta
            ) {
                $consultarDocDteCedibleRespuesta->descResp = \str_replace(
                    [
                        'iï¿½n ',
                        'rï¿½dito',
                        'ï¿½a',
                        ' sï¿½lo ',
                    ],
                    [
                        'ión ',
                        'rédito',
                        'ía',
                        ' sólo ',
                    ],
                    $consultarDocDteCedibleRespuesta->descResp
                );
                $this->setResult($consultarDocDteCedibleRespuesta);

                return $consultarDocDteCedibleRespuesta;
            });
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return false;
        }
    }

    /**
     * Method to call the operation originally named ingresarAceptacionReclamoDoc.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $rutEmisor
     * @param string $dvEmisor
     * @param string $tipoDoc
     * @param string $folio
     * @param string $accionDoc
     *
     * @return false|string|WsRegistroReclamoDte\RespuestaTo
     */
    public function ingresarAceptacionReclamoDoc($rutEmisor, $dvEmisor, $tipoDoc, $folio, $accionDoc)
    {
        try {
            $this->setResult($this->getSoapClient()->__soapCall('ingresarAceptacionReclamoDoc', [
                $rutEmisor,
                $dvEmisor,
                $tipoDoc,
                $folio,
                $accionDoc,
            ], [], [], $this->outputHeaders));

            return $this->getResult();
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return false;
        }
    }

    /**
     * Method to call the operation originally named consultarFechaRecepcionSii.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $rutEmisor
     * @param string $dvEmisor
     * @param string $tipoDoc
     * @param string $folio
     *
     * @return false|string|WsRegistroReclamoDte\RespuestaTo
     */
    public function consultarFechaRecepcionSii($rutEmisor, $dvEmisor, $tipoDoc, $folio)
    {
        try {
            $this->setResult($this->getSoapClient()->__soapCall('consultarFechaRecepcionSii', [
                $rutEmisor,
                $dvEmisor,
                $tipoDoc,
                $folio,
            ], [], [], $this->outputHeaders));

            return $this->getResult();
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return false;
        }
    }

    /**
     * Method to call the operation originally named listarEventosHistDoc.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @return false|string|WsRegistroReclamoDte\RespuestaTo
     */
    public function listarEventosHistDoc(...$args)
    {
        try {
            $this->setResult($this->consultarDocDteCedibleAsync(...$args));

            return $this->getResult();
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return false;
        } catch (\Exception $e) {
            dump($e->getMessage());

            return false;
        }
    }

    /**
     * Method to call the operation originally named listarEventosHistDoc.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $rutEmisor
     * @param string $dvEmisor
     * @param string $tipoDoc
     * @param string $folio
     */
    public function listarEventosHistDocAsync($rutEmisor, $dvEmisor, $tipoDoc, $folio): PromiseInterface
    {
        return $this->getAsyncSoapClient()->listarEventosHistDoc(
            $rutEmisor,
            $dvEmisor,
            $tipoDoc,
            $folio
        )->then(function ($result) {
            $this->setResult($result);

            return $result;
        });
    }

    /**
     * Returns the result.
     *
     * @see AbstractSoapClientBase::getResult()
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\RespuestaTo|string
     */
    public function getResult()
    {
        // dump([get_class($this) => 'getResult']);
        return parent::getResult();
    }
}
