<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\Soap\SoapInterpreter;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Stream;

 class SoapHttpBinding extends HttpBinding
 {
     private SoapInterpreter $interpreter;

     private RequestBuilder $builder;

     public function __construct(SoapInterpreter $interpreter, RequestBuilder $builder)
     {
         $this->interpreter = $interpreter;
         $this->builder = $builder;
     }

     /**
      * Embed SOAP messages in PSR-7 HTTP Requests.
      *
      * @param string                       $name         the name of the SOAP function to bind
      * @param array<array-key, mixed>      $arguments    an array of the arguments to the SOAP function
      * @param array<array-key, mixed>|null $options      An associative array of options.
      *                                                   The location option is the URL of the remote Web service.
      *                                                   The uri option is the target namespace of the SOAP service.
      *                                                   The soapaction option is the action to call.
      * @param null|mixed                   $inputHeaders an array of headers to be bound along with the SOAP request
      *
      * @throws RequestException if SOAP HTTP binding failed using the given parameters
      *
      * @return \Zend\Diactoros\Request
      */
     public function request(
         $name,
         array $arguments,
         ?array $options = null,
         $inputHeaders = null
     ) {
         try {
             $soapRequest = $this->interpreter->request($name, $arguments, $options, $inputHeaders);

             if ($soapRequest->getSoapVersion() === '1') {
                 $this->builder->isSOAP11();
             } else {
                 $this->builder->isSOAP12();
             }
             $this->builder->setEndpoint($soapRequest->getEndpoint());
             $this->builder->setSoapAction($soapRequest->getSoapAction());

             $stream = new Stream('php://temp', 'r+');
             $stream->write($soapRequest->getSoapMessage());
             $stream->rewind();
             $this->builder->setSoapMessage($stream);

             try {
                 return $this->builder->getSoapHttpRequest();
             } catch (RequestException $exception) {
                 $stream->close();

                 throw $exception;
             }
         } catch (\Exception $exception) {
             debuglog()->warning($exception);

             throw $exception;
         }
     }

     public function getInterpreter(): SoapInterpreter
     {
         return $this->interpreter;
     }

     /**
      * Retrieve SOAP messages from PSR-7 HTTP responses.
      *
      * @param string                       $name          the name of the SOAP function to unbind
      * @param array<array-key, mixed>|null $outputHeaders if supplied, this array will be filled with the headers from
      *                                                    the SOAP response
      *
      * @throws \SoapFault if the underlying SOAP interpreter throws \SoapFault
      *
      * @return mixed
      */
     public function response(ResponseInterface $response, $name, ?array &$outputHeaders = null)
     {
         return $this->interpreter->response($response->getBody()->__toString(), $name, $outputHeaders);
     }
 }
