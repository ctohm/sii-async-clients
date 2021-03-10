<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding;

use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Request;
use Zend\Diactoros\Stream;

 /**
  * This class create PSR HTTP requests that embed SOAP messages.
  *
  * @internal
  * @psalm-internal CTOhm\SiiAsyncClients\Wsdl
  */
 class RequestBuilder
 {
     public const SOAP11 = '1.1';
     public const SOAP12 = '1.2';

     /**
      * @var string
      */
     private $endpoint;

     /**
      * @var string
      */
     private $soapVersion = self::SOAP11;

     /**
      * @var string
      */
     private $soapAction = '';

     /**
      * @var StreamInterface
      */
     private $soapMessage;

     /**
      * @var bool
      */
     private $hasSoapMessage = false;

     /**
      * @var string
      */
     private $httpMethod = 'POST';

     /**
      * @throws RequestException
      */
     public function getSoapHttpRequest(): Request
     {
         $this->validate();
         $headers = $this->prepareHeaders();
         $message = $this->prepareMessage();
         $request = new Request(
             $this->endpoint,
             $this->httpMethod,
             $message,
             $headers
         );
         $this->unsetAll();

         return $request;
     }

     /**
      * @param string $endpoint
      *
      * @return static
      */
     public function setEndpoint($endpoint): self
     {
         $this->endpoint = $endpoint;

         return $this;
     }

     /**
      * @return static
      */
     public function isSOAP11(): self
     {
         $this->soapVersion = self::SOAP11;

         return $this;
     }

     /**
      * @return static
      */
     public function isSOAP12(): self
     {
         $this->soapVersion = self::SOAP12;

         return $this;
     }

     /**
      * @param string $soapAction
      *
      * @return static
      */
     public function setSoapAction($soapAction): self
     {
         $this->soapAction = $soapAction;

         return $this;
     }

     /**
      * @return static
      */
     public function setSoapMessage(StreamInterface $message): self
     {
         $this->soapMessage = $message;
         $this->hasSoapMessage = true;

         return $this;
     }

     private function validate(): void
     {
         $isValid = true;

         if (!$this->endpoint) {
             $isValid = false;
         }

         if (!$this->hasSoapMessage && 'POST' === $this->httpMethod) {
             $isValid = false;
         }

         /**
          * SOAP 1.1 only defines HTTP binding with POST method.
          *
          * @see https://www.w3.org/TR/2000/NOTE-SOAP-20000508/#_Toc478383527
          */
         if (self::SOAP11 === $this->soapVersion && 'POST' !== $this->httpMethod) {
             $isValid = false;
         }

         /**
          * SOAP 1.2 only defines HTTP binding with POST and GET methods.
          *
          * @see https://www.w3.org/TR/2007/REC-soap12-part0-20070427/#L10309
          */
         if (self::SOAP12 === $this->soapVersion && !\in_array($this->httpMethod, ['GET', 'POST'], true)) {
             $isValid = false;
         }

         if (!$isValid) {
             $this->unsetAll();

             throw new RequestException();
         }
     }

     /**
      * @return (int|null|string)[]
      *
      * @psalm-return array{Accept?: string, Content-Length?: int|null, Content-Type?: string, SOAPAction?: string}
      */
     private function prepareHeaders()
     {
         if (self::SOAP11 === $this->soapVersion) {
             return $this->prepareSoap11Headers();
         }

         return $this->prepareSoap12Headers();
     }

     /**
      * @see https://www.w3.org/TR/2000/NOTE-SOAP-20000508/#_Toc478383526
      *
      * @return (int|null|string)[]
      *
      * @psalm-return array{Content-Length: int|null, SOAPAction: string, Content-Type: string}
      */
     private function prepareSoap11Headers(): array
     {
         $headers = [];
         $headers['Content-Length'] = $this->soapMessage->getSize();
         $headers['SOAPAction'] = $this->soapAction;
         $headers['Content-Type'] = 'text/xml; charset="utf-8"';

         return $headers;
     }

     /**
      * SOSPAction header is removed in SOAP 1.2 and now expressed as a value of
      * an (optional) "action" parameter of the "application/soap+xml" media type.
      *
      * @see https://www.w3.org/TR/soap12-part0/#L4697
      *
      * @return (int|null|string)[]
      *
      * @psalm-return array{Accept?: string, Content-Length?: int|null, Content-Type?: string}
      */
     private function prepareSoap12Headers(): array
     {
         $headers = [];

         if ('POST' === $this->httpMethod) {
             $headers['Content-Length'] = $this->soapMessage->getSize();
             $headers['Content-Type'] = 'application/soap+xml; charset="utf-8"' . '; action="' . $this->soapAction . '"';
         } else {
             $headers['Accept'] = 'application/soap+xml';
         }

         return $headers;
     }

     /**
      * @return StreamInterface
      */
     private function prepareMessage()
     {
         if ('POST' === $this->httpMethod) {
             return $this->soapMessage;
         }

         return new Stream('php://temp', 'r');
     }

     private function unsetAll(): void
     {
         $this->endpoint = null;

         if ($this->hasSoapMessage) {
             $this->soapMessage = null;
             $this->hasSoapMessage = false;
         }
         $this->soapAction = '';
         $this->soapVersion = self::SOAP11;
         $this->httpMethod = 'POST';
     }
 }
