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
    /**
     * Minimal options.
     */
    protected static $clientOptions = [
        WsdlClientBase::WSDL_URL => 'https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL',
        WsdlClientBase::WSDL_CLASSMAP => [],
    ];

    public function __construct(array $clientOptions = [])
    {
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
            // Gets rid of all namespace definitions
            // $xml_string = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $soapResultBody);

            // Gets rid of all namespace references
            //$xml_string = preg_replace('/[a-zA-Z]+?:([a-zA-Z]+\s*[=>])/', '$1', $xml_string);

            $xml = \is_string($soapResultBody) ? new \SimpleXMLElement($soapResultBody, \LIBXML_COMPACT) : $soapResultBody;

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
