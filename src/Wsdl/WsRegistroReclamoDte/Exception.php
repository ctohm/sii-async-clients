<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte;

use WsdlToPhp\PackageBase\AbstractStructBase;

/**
 * This class stands for Exception StructType
 * Meta information extracted from the WSDL
 * - type: tns:Exception.
 */
final class Exception extends AbstractStructBase
{
    /**
     * The message
     * Meta information extracted from the WSDL
     * - minOccurs: 0.
     *
     * @var string
     */
    public $message;

    /**
     * Constructor method for Exception.
     *
     * @uses Exception::setMessage()
     *
     * @param string $message
     */
    public function __construct($message = null)
    {
        $this
            ->setMessage($message);
    }

    /**
     * Get message value.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set message value.
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\Exception
     */
    public function setMessage(?string $message = null): self
    {
        $this->message = $message;

        return $this;
    }
}
