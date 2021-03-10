<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\AsyncSoap;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding\RequestBuilder;
use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding\SoapHttpBinding;
use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\Soap\SoapInterpreter;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Http\Message\ResponseInterface;

 /**
  * @internal
  * @psalm-internal CTOhm\SiiAsyncClients\Wsdl
  */
 class SoapClientFactory
 {
     /**
      * @var SoapInterpreter
      */
     private $interpreter;

     /**
      * This method will load WSDL asynchronously if the given WSDL URI is a HTTP URL.
      *
      * @param ClientInterface         $client  a Guzzle HTTP client
      * @param mixed                   $wsdl    URI of the WSDL file or NULL if working in non-WSDL mode
      * @param array<array-key, mixed> $options Supported options: location, uri, style, use, soap_version, encoding,
      *                                         exceptions, classmap, typemap, and feature. HTTP related options should
      *                                         be configured against $client, e.g., authentication, proxy, user agent,
      *                                         and connection timeout etc.
      */
     public function create(ClientInterface $client, $wsdl, array $options = []): AsyncSoapClient
     {
         if ($this->isHttpUrl($wsdl)) {
             $httpBindingPromise = $client->requestAsync('GET', $wsdl)->then(
                 function (ResponseInterface $response) use ($options) {
                     $wsdlContent = $response->getBody()->__toString();
                     $this->interpreter = new Soap\SoapInterpreter('data://text/plain;base64,' . \base64_encode($wsdlContent), $options);

                     return new HttpBinding\SoapHttpBinding($this->interpreter, new HttpBinding\RequestBuilder());
                 },
                 function ($errInBinding) use ($wsdl, $options) {
                     $this->interpreter = new Soap\SoapInterpreter($wsdl, $options);

                     return new FulfilledPromise(
                         new HttpBinding\SoapHttpBinding($this->interpreter, new HttpBinding\RequestBuilder())
                     );
                 }
             );
         } else {
             $this->interpreter = new SoapInterpreter($wsdl, $options);
             $httpBindingPromise = new FulfilledPromise(
                 new SoapHttpBinding($this->interpreter, new RequestBuilder())
             );
         }

         return new AsyncSoapClient($client, $httpBindingPromise);
     }

     private function isHttpUrl($wsdl): bool
     {
         return \filter_var($wsdl, \FILTER_VALIDATE_URL) !== false
            && \in_array(\parse_url($wsdl, \PHP_URL_SCHEME), ['http', 'https'], true);
     }
 }
