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
 * This class stands for Get ServiceType.
 *
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients
 */
final class QueryEstDteAvanzadoClient extends QueryEstDteClient
{
    /**
     *  Minimal options.
     *
     * @var (string|string[])[]
     */
    protected static $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://palena.sii.cl/DTEWS/services/QueryEstDteAv?wsdl',
        WsdlClientBase::LOCAL_FILE => 'wsdl/palena/QueryEstDteAv.jws',
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
     * Method to call the operation originally named getEstDteAv.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $rutEmpresa
     * @param string $dvEmpresa
     * @param string $rutReceptor
     * @param string $dvReceptor
     * @param string $tipoDte
     * @param string $folioDte
     * @param string $fechaEmisionDte
     * @param string $montoDte
     * @param string $firmaDte
     * @param string $token
     *
     * @return false|string
     */
    public function getEstDteAv(
        $rutEmpresa,
        $dvEmpresa,
        $rutReceptor,
        $dvReceptor,
        $tipoDte,
        $folioDte,
        $fechaEmisionDte,
        $montoDte,
        $firmaDte,
        $token
    ) {
        try {
            $this->setResult($this->getSoapClient()->getEstDteAv(
                $rutEmpresa,
                $dvEmpresa,
                $rutReceptor,
                $dvReceptor,
                $tipoDte,
                $folioDte,
                $fechaEmisionDte,
                $montoDte,
                $firmaDte,
                $token
            ));

            return $this->getResult();
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(
                __METHOD__,
                $soapFault
            );

            return false;
        }
    }

    /**
     * Method to call the operation originally named getEstDteAv.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param string $rutEmpresa
     * @param string $dvEmpresa
     * @param string $rutReceptor
     * @param string $dvReceptor
     * @param string $tipoDte
     * @param string $folioDte
     * @param string $fechaEmisionDte
     * @param string $montoDte
     * @param string $firmaDte
     * @param string $token
     */
    public function getEstDteAvAsync(
        $rutEmpresa,
        $dvEmpresa,
        $rutReceptor,
        $dvReceptor,
        $tipoDte,
        $folioDte,
        $fechaEmisionDte,
        $montoDte,
        $firmaDte,
        $token
    ): PromiseInterface {
        return $this->getAsyncSoapClient()->getEstDteAv(
            $rutEmpresa,
            $dvEmpresa,
            $rutReceptor,
            $dvReceptor,
            $tipoDte,
            $folioDte,
            $fechaEmisionDte,
            $montoDte,
            $firmaDte,
            $token
        )->then(static function (
            string $getEstDteRespuesta
        ) {
            // dump($getEstDteRespuesta);

            return self::parseSIIRespuesta($getEstDteRespuesta);
        });
    }
}
