<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient;
use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\RegistroReclamoAsyncClient;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\WsdlClientBase;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * This class stands for Listar ServiceType.
 * @template-extends WsdlClientBase<RegistroReclamoAsyncClient>
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients
 */
final class RegistroReclamoDteClient extends WsdlClientBase
{
    /**
     * Undocumented variable
     * @psalm-var  WsdlClientBase<RegistroReclamoAsyncClient>
     * @var \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient
     */
    protected static  $asyncSoapClient = null;
    /**
     * @var string
     */
    public const WSDL_SLUG = 'registro_reclamo_dte_service';

    /**
     * Minimal options.
     */
    protected static array $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://ws1.sii.cl/WSREGISTRORECLAMODTE/registroreclamodteservice?wsdl',

        WsdlClientBase::LOCAL_FILE => __DIR__ . '/../resources/wsdl/registroreclamodteservice.jws',

        WsdlClientBase::WSDL_CLASSMAP => [
            'respuestaTo' => \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\RespuestaTo::class,
            'DteEventoDocTo' => \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo::class,
            'Exception' => \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\Exception::class,
        ],
    ];


    public function __construct(array $clientOptions = [])
    {
        self::$clientOptions[WsdlClientBase::LOCAL_FILE] = config(\sprintf('sii-clients.%s', self::WSDL_SLUG), self::$clientOptions[WsdlClientBase::LOCAL_FILE]);
        $this->mergedClientOptions = \array_merge(self::$clientOptions, $clientOptions);
        $this->mergedClientOptions['classmap'] = self::$clientOptions[WsdlClientBase::WSDL_CLASSMAP];
        parent::__construct($this->mergedClientOptions);

        if ($clientOptions['soapToken'] ?? null) {
            $this->setToken($clientOptions['soapToken']);
        }
        //  kdump($this->getSoapClientClassName());
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
        return $this->consultarDocDteCedibleAsync(
            ...$args
        )->wait();
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
        return $this->getAsyncSoapClient(RegistroReclamoAsyncClient::class)->consultarDocDteCedible(
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
        return $this->getAsyncSoapClient(RegistroReclamoAsyncClient::class)->callAsync('consultarFechaRecepcionSii', [
            $rutEmisor,
            $dvEmisor,
            $tipoDoc,
            $folio,
        ], [], [], $this->outputHeaders)->then(function ($result) {
            return tap($result, fn ($result) => $this->setResult($result));
        })->otherwise(function ($soapFault) {
            return tap(false, fn () => $this->saveLastError('getEstDte', $soapFault));
        });
    }

    /**
     * Method to call the operation originally named listarEventosHistDoc.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param mixed $rutEmisor
     * @param mixed $dvEmisor
     * @param mixed $tipoDoc
     * @param mixed $folio
     */
    public function listarEventosHistDoc($rutEmisor, $dvEmisor, $tipoDoc, $folio): PromiseInterface
    {
        $result = $this->getSoapClient()->__soapCall('listarEventosHistDoc', [$rutEmisor, $dvEmisor, $tipoDoc, $folio], [], [], $this->outputHeaders);

        return (new FulfilledPromise($result))->then(function ($result) {
            $this->setResult($result);
            kdump($result);

            return $this->getResult();
        })->otherwise(function (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return false;
        });
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
        return $this->getAsyncSoapClient(RegistroReclamoAsyncClient::class)->listarEventosHistDoc(
            $rutEmisor,
            $dvEmisor,
            $tipoDoc,
            $folio
        )->then(function ($result) {
            return tap($result, fn ($result) => $this->setResult($result));
        })->otherwise(function ($soapFault) {
            return tap(false, fn () => $this->saveLastError('getEstDte', $soapFault));
        });
    }

    /**
     * Returns the result.
     *
     * @see AbstractSoapClientBase::getResult()
     *
     * @return null|string
     */
    public function getResult()
    {
        // dump([get_class($this) => 'getResult']);
        return parent::getResult();
    }
}
