<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\Soap;

 class SoapRequest
 {
     /**
      * @var string
      */
     private $endpoint;

     /**
      * @var string
      */
     private $soapAction;

     /**
      * @var string
      */
     private $soapVersion;

     /**
      * @var string
      */
     private $soapMessage;

     /**
      * @param string $endpoint
      * @param string $soapAction
      * @param string $soapVersion
      * @param string $soapMessage
      */
     public function __construct($endpoint, $soapAction, $soapVersion, $soapMessage)
     {
         $this->endpoint = $endpoint;
         $this->soapAction = $soapAction;
         $this->soapVersion = $soapVersion;
         $this->soapMessage = $soapMessage;
     }

     /**
      * @return string
      */
     public function getEndpoint()
     {
         return $this->endpoint;
     }

     /**
      * @return string
      */
     public function getSoapAction()
     {
         return $this->soapAction;
     }

     /**
      * @return string
      */
     public function getSoapVersion()
     {
         return $this->soapVersion;
     }

     /**
      * @return string
      */
     public function getSoapMessage()
     {
         return $this->soapMessage;
     }
 }
