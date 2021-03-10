<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\Guzzle;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding\HttpBinding;
use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding\RequestBuilder;
use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\Soap\Interpreter;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Http\Message\ResponseInterface;

 class Factory
 {
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
     public function create(ClientInterface $client, $wsdl, array $options = []): SoapClient
     {
         if (false && $this->isHttpUrl($wsdl)) {
             $httpBindingPromise = $client->requestAsync('GET', $wsdl)->then(
                 static function (ResponseInterface $response) use ($options) {
                     $wsdl = $response->getBody()->__toString();
                     $interpreter = new \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\Soap\Interpreter('data://text/plain;base64,' . \base64_encode($wsdl), $options);

                     return new \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding\HttpBinding($interpreter, new \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding\RequestBuilder());
                 }
             );
         } else {
             $httpBindingPromise = new FulfilledPromise(
                 new HttpBinding(new Interpreter($wsdl, $options), new RequestBuilder())
             );
         }

         return new SoapClient($client, $httpBindingPromise);
     }

     private function isHttpUrl($wsdl): bool
     {
         return \filter_var($wsdl, \FILTER_VALIDATE_URL) !== false
            && \in_array(\parse_url($wsdl, \PHP_URL_SCHEME), ['http', 'https'], true);
     }
 }
