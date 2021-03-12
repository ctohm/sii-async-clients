<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\SoapClients;

/**
 * This class stands for Get ServiceType.
 *
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients\Wsdl
 */
final class CrSeedClient extends WsdlClientBase
{
    public const WSDL_SLUG = 'cr_seed';

    /**
     * Minimal options.
     */
    protected static $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL',
        WsdlClientBase::LOCAL_FILE => __DIR__ . '/../../resources/wsdl/CrSeed.jws',

        WsdlClientBase::WSDL_CLASSMAP => [],
    ];

    public function __construct(array $clientOptions = [])
    {
        self::$clientOptions[WsdlClientBase::LOCAL_FILE] = config(\sprintf('sii-clients.%s', self::WSDL_SLUG), self::$clientOptions[WsdlClientBase::LOCAL_FILE]);
        parent::__construct(\array_merge(self::$clientOptions, $clientOptions));
    }

    /**
     * Method to call the operation originally named getSeed.
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
    public function getSeed(
        $loopOptions,
        ...$args
    ) {
        $loopOptions['retryAttempts'] = (int) ($loopOptions['retryAttempts'] ?? 1);
        $loopOptions['retriesSoFar'] = (int) ($loopOptions['retriesSoFar'] ?? 0);

        try {
            $this->setResult($this->getSoapClient()->getSeed());
            $soapResultBody = $this->getResult();
            $this->logSoapResponse($soapResultBody, __METHOD__, $args ?? [], $loopOptions);

            $xml = new \SimpleXMLElement($soapResultBody, \LIBXML_COMPACT);

            if (
                false === $xml
                || '00' !== (string) $xml
                    ->xpath('/SII:RESPUESTA/SII:RESP_HDR/ESTADO')[0]
            ) {
                app('log')->warning('Error al obtener semilla');

                return false;
            }

            return (string) $xml->xpath('/SII:RESPUESTA/SII:RESP_BODY/SEMILLA')[0];
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            if ($loopOptions['retriesSoFar'] >= $loopOptions['retryAttempts']) {
                throw $soapFault;
            }
            app('log')->warning($soapFault);
            \usleep(500000 * \min(8, 2 ^ $loopOptions['retriesSoFar'])); // wait 0.5 secs, then 1, then 2, then 4 (capped at 4 too)

            return $this->getSeed($loopOptions, ...$args);
        }
    }
}
