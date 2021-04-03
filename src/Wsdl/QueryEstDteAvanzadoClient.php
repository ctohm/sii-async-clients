<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\QueryEstDteAvAsyncClient;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\WsdlClientBase;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * This class stands for Get ServiceType.
 *
 *
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients
 */
final class QueryEstDteAvanzadoClient extends QueryEstDteClient
{
    public const WSDL_SLUG = 'query_est_dte_av';

    /**
     * Undocumented variable.
     * @template-extends \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient<QueryEstDteAvAsyncClient>
     *
     * @psalm-var \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient<QueryEstDteAvAsyncClient>
     *
     * @var \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient<QueryEstDteAvAsyncClient>
     */
    protected static $asyncSoapClient = null;

    /**
     *  Minimal options.
     *
     * @var (string|string[])[]
     */
    protected static $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://palena.sii.cl/DTEWS/services/QueryEstDteAv?wsdl',

        WsdlClientBase::LOCAL_FILE => __DIR__ . '/../resources/wsdl/QueryEstDteAv.jws',
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
            $this->getAsyncSoapClient();
        }
    }

    /**
     * @param array $args
     *
     * @return false|string
     */
    public function getEstDteAv(
        ...$args
    ) {
        return $this->getEstDteAvAsync(
            ...$args
        )->wait();
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
        return $this->getAsyncSoapClient(QueryEstDteAvAsyncClient::class)->getEstDteAv(
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
        )->then(function (
            string $getEstDteRespuesta
        ) {
            return tap(self::parseSIIRespuesta($getEstDteRespuesta), fn ($result) => $this->setResult($result));
        })->otherwise(function ($soapFault) {
            return tap(false, fn () => $this->saveLastError('getEstDte', $soapFault));
        });
    }
}
