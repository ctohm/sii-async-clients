<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\Soap;

 class Interpreter
 {
     /**
      * @var Soap
      */
     private $soap;

     /**
      * @param mixed                   $wsdl    URI of the WSDL file or NULL if working in non-WSDL mode
      * @param array<array-key, mixed> $options supported options: location, uri, style, use, soap_version, encoding,
      *                                         exceptions, classmap, typemap, cache_wsdl and feature
      */
     public function __construct(
         $wsdl,
         array $options = []
     ) {
         $this->soap = new Soap($wsdl, $options);
     }

     /**
      * Interpret the given method and arguments to a SOAP request message.
      *
      * @param string                       $function_name the name of the SOAP function to interpret
      * @param array<array-key, mixed>      $arguments     an array of the arguments to $function_name
      * @param array<array-key, mixed>|null $options       An associative array of options.
      *                                                    The location option is the URL of the remote Web service.
      *                                                    The uri option is the target namespace of the SOAP service.
      *                                                    The soapaction option is the action to call.
      * @param null|mixed                   $input_headers an array of headers to be interpreted along with the SOAP request
      *
      * @return SoapRequest
      */
     public function request(
         $function_name,
         array $arguments = [],
         ?array $options = null,
         $input_headers = null
     ) {
         return $this->soap->request($function_name, $arguments, $options, $input_headers);
     }

     /**
      * Interpret a SOAP response message to PHP values.
      *
      * @param string                       $response       the SOAP response message
      * @param string                       $function_name  the name of the SOAP function to interpret
      * @param array<array-key, mixed>|null $output_headers if supplied, this array will be filled with the headers from the SOAP response
      *
      * @throws \SoapFault
      *
      * @return mixed
      */
     public function response($response, $function_name, ?array &$output_headers = null)
     {
         return $this->soap->response($response, $function_name, $output_headers);
     }
 }
