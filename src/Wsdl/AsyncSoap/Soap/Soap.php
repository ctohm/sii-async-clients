<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\Soap;

use Log;

 /**
  * @internal
  * @internal
  * @psalm-internal CTOhm\SiiAsyncClients\Wsdl
  */
 class Soap extends \SoapClient
 {
     /**
      * @var string
      */
     private $endpoint;

     /**
      * @var string
      */
     private $soapRequest;

     /**
      * @var null|string
      */
     private $soapResponse;

     /**
      * @var string
      */
     private $soapAction;

     /**
      * @var numeric-string
      */
     private string $soapVersion;

     public function __construct(
         $wsdl,
         array $options
     ) {
         unset(
            $options['login'],
            $options['password'],
            $options['proxy_host'],
            $options['proxy_port'],
            $options['proxy_login'],
            $options['proxy_password'],
            $options['local_cert'],
            $options['passphrase'],
            $options['authentication'],
            $options['compression'],
            $options['trace'],
            $options['connection_timeout'],
            $options['user_agent'],
            $options['stream_context'],
            $options['keep_alive'],
            $options['ssl_method']
        );

         parent::__construct($wsdl, $options);
     }

     public function __doRequest(
         $request,
         $location,
         $action,
         $version,
         $one_way = 0
     ) {
         if (null !== $this->soapResponse) {
             return $this->soapResponse;
         }

         $this->endpoint = (string) $location;
         $this->soapAction = (string) $action;
         $this->soapVersion = (string) $version;
         $this->soapRequest = (string) $request;

         return '';
     }

     /**
      * @param mixed $input_headers
      *
      * @return SoapRequest
      */
     public function request(
         string $function_name,
         array $arguments,
         ?array $options,
         $input_headers
     ) {
         try {
             $this->__soapCall(
                 $function_name,
                 $arguments,
                 $options,
                 $input_headers
             );

             return new SoapRequest($this->endpoint, $this->soapAction, $this->soapVersion, $this->soapRequest);
         } catch (\Exception $e) {
             Log::error($e);

             throw $e;
         }
     }

     public function response(string $response, string $function_name, ?array &$output_headers)
     {
         $this->soapResponse = $response;

         try {
             $response = $this->__soapCall($function_name, [], null, null, $output_headers);
         } catch (\SoapFault $fault) {
             $this->soapResponse = null;

             throw $fault;
         }
         $this->soapResponse = null;

         return $response;
     }
 }
