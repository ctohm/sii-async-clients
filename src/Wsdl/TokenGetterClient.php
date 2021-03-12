<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl;

use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\CrSeedClient;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\WsdlClientBase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use WsdlToPhp\PackageBase\AbstractSoapClientBase;

/**
 * This class stands for Get ServiceType.
 *
 * @ internal
 * @psalm-internal CTOhm\SiiAsyncClients
 */
final class TokenGetterClient extends WsdlClientBase
{
    public const WSDL_SLUG = 'get_token_from_seed';

    /**
     * Minimal options.
     */
    protected static $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL',
        WsdlClientBase::WSDL_CLASSMAP => [],
        WsdlClientBase::LOCAL_FILE => __DIR__ . '/../resources/wsdl/GetTokenFromSeed.jws',

        WsdlClientBase::WSDL_TRACE => true,
        WsdlClientBase::WSDL_CACHE_WSDL => \WSDL_CACHE_NONE,
    ];

    private static ?self $instance = null;

    public function __construct(array $clientOptions = [])
    {
        self::$clientOptions[WsdlClientBase::LOCAL_FILE] = config(\sprintf('sii-clients.%s', self::WSDL_SLUG), self::$clientOptions[WsdlClientBase::LOCAL_FILE]);
        parent::__construct(\array_merge(self::$clientOptions, $clientOptions));
    }

    public static function getInstance(array $clientOptions = []): self
    {
        if (!self::$instance) {
            self::$instance = new self($clientOptions);
        }

        return self::$instance;
    }

    /**
     * Undocumented function.
     *
     * @return string
     */
    public function getCachedOrRenewedToken(SiiSignatureInterface $siiSignature, array $loopOptions = [])
    {
        $soapToken = null;

        if (Cache::has('soapToken')) {
            $soapToken = Cache::get('soapToken');

        // kdump([__CLASS__ => \sprintf('Using cached token %s', $soapToken)]);
        } else {
            $soapToken = Cache::remember('soapToken', 300, function () use ($siiSignature, $loopOptions) {
                $loopOptions = \array_merge(
                    [
                        'retryAttempts' => 2,
                        'retriesSoFar' => 0,
                    ],
                    $loopOptions
                );
                $signedSeed = $this->getSignedTokenRequest($siiSignature, $loopOptions);

                return $this->getToken(
                    $loopOptions,
                    $signedSeed
                );
            });
        }

        if (null === $soapToken) {
            throw new \Exception('No se pudo obtener un token del SII');
        }

        return $soapToken;
    }

    /**
     * gets a  una semilla previamente obtenida.
     *
     * @return string signed seed
     */
    public function getSignedTokenRequest(SiiSignatureInterface $siiSignature, array $loopOptions = []): string
    {
        $unsignedTokenRequestXML = $this->getTokenRequest($loopOptions);

        if (!$seedSignedXML = $siiSignature->signXMLDoc(
            $unsignedTokenRequestXML
        )) {
            throw new \Exception('Could not sign the request to get SII Token', 1);
        }

        return $seedSignedXML;
    }

    /**
     * Method to call the operation originally named getToken.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     *
     * @param mixed $loopOptions
     *
     * @return bool|string
     */
    public function getToken(
        $loopOptions,
        string ...$args
    ) {
        $loopOptions['retryAttempts'] = (int) ($loopOptions['retryAttempts'] ?? 1);
        $loopOptions['retriesSoFar'] = (int) ($loopOptions['retriesSoFar'] ?? 0);

        try {
            $this->setResult($this->getSoapClient()->getToken(...$args));
            $soapResultBody = $this->getResult();

            $xml = new \SimpleXMLElement($soapResultBody, \LIBXML_COMPACT);

            $responseEstado = (string) $xml
                ->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0];

            if ('00' !== $responseEstado) {
                throw new \Exception(\sprintf(
                    'Could not get a token from the SII %s',
                    $responseEstado
                ));
            }

            return (string) $xml
                ->xpath('/SII:RESPUESTA/SII:RESP_BODY/TOKEN')[0];
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            if ($loopOptions['retriesSoFar'] >= $loopOptions['retryAttempts']) {
                throw $soapFault;
            }
            Log::warning($soapFault);
            \usleep(
                config('sii-clients.default_request_delay_ms') * 5 * 1000  // milliseconds * 1000 to deal in microseconds, 5x backoff
                    * \min(8, 2 ^ $loopOptions['retriesSoFar'])
            ); // wait 0.5 secs, then 1, then 2, then 4 (capped at 4 too)

            return $this->getToken($loopOptions, ...$args);
        }
    }

    /**
     * @return mixed
     */
    public function getTokenRequest(array $loopOptions = [])
    {
        $seedClient = new CrSeedClient([
            'cache_wsdl' => config('sii-clients.cache_policy'),
            'trace' => true,
            'exceptions' => true,
            'keep_alive' => false,
            'delay' => config('sii-clients.default_request_delay_ms'),  // milliseconds * 1000 to deal in microseconds,
            'http_errors' => false,
            AbstractSoapClientBase::WSDL_CACHE_WSDL => config('sii-clients.cache_policy'),
        ]);
        $loopOptions = \array_merge(
            [
                'retryAttempts' => 2,
                'retriesSoFar' => 0,
            ],
            $loopOptions
        );

        return tap(
            new \DOMDocument('1.0', 'ISO-8859-1'),
            static function ($dom) use ($seedClient, $loopOptions) {
                return $dom->loadXML(
                    \sprintf(
                        '<getToken><item><Semilla>%s</Semilla></item></getToken>',
                        $seedClient->getSeed($loopOptions)
                    )
                );
            }
        );
    }
}
