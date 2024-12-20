<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\AsyncSoap;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use Meng\AsyncSoap\SoapClientInterface;
use Meng\Soap\HttpBinding\HttpBinding;
use Meng\Soap\HttpBinding\RequestBuilder;
use Meng\Soap\Interpreter;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients\Wsdl
 */
class SoapClientFactory
{
    private Interpreter $interpreter;

    /**
     * Create an instance of SoapClientInterface.
     *
     * This method will load WSDL asynchronously if the given WSDL URI is a HTTP URL.
     *
     * @param ClientInterface $client  a Guzzle HTTP client
     * @param mixed           $wsdl    URI of the WSDL file or NULL if working in non-WSDL mode
     * @param array           $options Supported options: location, uri, style, use, soap_version, encoding,
     *                                 exceptions, classmap, typemap, and feature. HTTP related options should
     *                                 be configured against $client, e.g., authentication, proxy, user agent,
     *                                 and connection timeout etc.
     *
     * @return SoapClientInterface
     */
    public function create(ClientInterface $client, $wsdl, array $options = [])
    {
        if ($this->isHttpUrl($wsdl)) {
            kdump('HTTPURL');
            $httpBindingPromise = $client->requestAsync('GET', $wsdl)->then(
                static function (ResponseInterface $response) use ($options) {
                    $wsdl = $response->getBody()->__toString();
                    $interpreter = new Interpreter('data://text/plain;base64,' . \base64_encode($wsdl), $options);

                    return new HttpBinding($interpreter, new RequestBuilder());
                }
            );
        } else {
            $httpBindingPromise = new FulfilledPromise(
                new HttpBinding(new Interpreter($wsdl, $options), new RequestBuilder())
            );
        }

        return new AsyncSoapClient($client, $httpBindingPromise);
    }

    /**
     * @param mixed $wsdl
     *
     * @return bool
     */
    private function isHttpUrl($wsdl)
    {
        return \filter_var($wsdl, \FILTER_VALIDATE_URL) !== false
            && \in_array(\parse_url($wsdl, \PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
