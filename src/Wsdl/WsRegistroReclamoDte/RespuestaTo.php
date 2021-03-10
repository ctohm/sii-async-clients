<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte;

use WsdlToPhp\PackageBase\AbstractStructBase;

/**
 * This class stands for respuestaTo StructType.
 */
final class RespuestaTo extends AbstractStructBase
{
    /**
     * The codResp
     * Meta information extracted from the WSDL
     * - minOccurs: 0.
     *
     * @var int
     */
    public $codResp;

    /**
     * The descResp
     * Meta information extracted from the WSDL
     * - minOccurs: 0.
     *
     * @var string
     */
    public $descResp;

    /**
     * The listaEventosDoc
     * Meta information extracted from the WSDL
     * - maxOccurs: unbounded
     * - minOccurs: 0
     * - nillable: true.
     *
     * @var \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo[]
     */
    public $listaEventosDoc;

    /**
     * The rutToken
     * Meta information extracted from the WSDL
     * - minOccurs: 0.
     *
     * @var string
     */
    public $rutToken;

    /**
     * Constructor method for respuestaTo.
     *
     * @uses RespuestaTo::setCodResp()
     * @uses RespuestaTo::setDescResp()
     * @uses RespuestaTo::setListaEventosDoc()
     * @uses RespuestaTo::setRutToken()
     *
     * @param int                                                               $codResp
     * @param string                                                            $descResp
     * @param \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo[] $listaEventosDoc
     * @param string                                                            $rutToken
     */
    public function __construct($codResp = null, $descResp = null, array $listaEventosDoc = [], $rutToken = null)
    {
        $this
            ->setCodResp($codResp)
            ->setDescResp($descResp)
            ->setListaEventosDoc($listaEventosDoc)
            ->setRutToken($rutToken);
    }

    /**
     * Get codResp value.
     *
     * @return int
     */
    public function getCodResp()
    {
        return $this->codResp;
    }

    /**
     * Set codResp value.
     *
     * @param int $codResp
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\RespuestaTo
     */
    public function setCodResp($codResp = null)
    {
        // validation for constraint: int
        if (null !== $codResp && !(\is_int($codResp) || \ctype_digit($codResp))) {
            throw new \InvalidArgumentException(\sprintf('Invalid value %s, please provide an integer value, %s given', \var_export($codResp, true), \gettype($codResp)), __LINE__);
        }
        $this->codResp = $codResp;

        return $this;
    }

    /**
     * Get descResp value.
     *
     * @return string
     */
    public function getDescResp()
    {
        return $this->descResp;
    }

    /**
     * Set descResp value.
     *
     * @param string $descResp
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\RespuestaTo
     */
    public function setDescResp($descResp = null)
    {
        // validation for constraint: string
        if (null !== $descResp && !\is_string($descResp)) {
            throw new \InvalidArgumentException(\sprintf('Invalid value %s, please provide a string, %s given', \var_export($descResp, true), \gettype($descResp)), __LINE__);
        }
        $this->descResp = $descResp;

        return $this;
    }

    /**
     * Get listaEventosDoc value
     * An additional test has been added (isset) before returning the property value as
     * this property may have been unset before, due to the fact that this property is
     * removable from the request (nillable=true+minOccurs=0).
     *
     * @return null|\CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo[]
     */
    public function getListaEventosDoc()
    {
        return $this->listaEventosDoc ?? null;
    }

    /**
     * This method is responsible for validating the values passed to the setListaEventosDoc method
     * This method is willingly generated in order to preserve the one-line inline validation within the setListaEventosDoc method.
     *
     * @return string A non-empty message if the values does not match the validation rules
     */
    public static function validateListaEventosDocForArrayConstraintsFromSetListaEventosDoc(array $values = [])
    {
        $message = '';
        $invalidValues = [];

        foreach ($values as $respuestaToListaEventosDocItem) {
            // validation for constraint: itemType
            if (!$respuestaToListaEventosDocItem instanceof \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo) {
                $invalidValues[] = \is_object($respuestaToListaEventosDocItem) ? \get_class($respuestaToListaEventosDocItem) : \sprintf('%s(%s)', \gettype($respuestaToListaEventosDocItem), \var_export($respuestaToListaEventosDocItem, true));
            }
        }

        if (!empty($invalidValues)) {
            $message = \sprintf('The listaEventosDoc property can only contain items of type \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo, %s given', \is_object($invalidValues) ? \get_class($invalidValues) : (\is_array($invalidValues) ? \implode(', ', $invalidValues) : \gettype($invalidValues)));
        }
        unset($invalidValues);

        return $message;
    }

    /**
     * Set listaEventosDoc value
     * This property is removable from request (nillable=true+minOccurs=0), therefore
     * if the value assigned to this property is null, it is removed from this object.
     *
     * @param \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo[] $listaEventosDoc
     *
     * @throws \InvalidArgumentException
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\RespuestaTo
     */
    public function setListaEventosDoc(array $listaEventosDoc = [])
    {
        // validation for constraint: array
        if ('' !== ($listaEventosDocArrayErrorMessage = self::validateListaEventosDocForArrayConstraintsFromSetListaEventosDoc($listaEventosDoc))) {
            throw new \InvalidArgumentException($listaEventosDocArrayErrorMessage, __LINE__);
        }

        if (empty($listaEventosDoc)) {
            $this->listaEventosDoc = [];
        } else {
            $this->listaEventosDoc = $listaEventosDoc;
        }

        return $this;
    }

    /**
     * Add item to listaEventosDoc value.
     *
     * @throws \InvalidArgumentException
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\RespuestaTo
     */
    public function addToListaEventosDoc(DteEventoDocTo $item)
    {
        // validation for constraint: itemType
        if (!$item instanceof \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo) {
            throw new \InvalidArgumentException(\sprintf('The listaEventosDoc property can only contain items of type \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo, %s given', \is_object($item) ? \get_class($item) : (\is_array($item) ? \implode(', ', $item) : \gettype($item))), __LINE__);
        }
        $this->listaEventosDoc[] = $item;

        return $this;
    }

    /**
     * Get rutToken value.
     *
     * @return string
     */
    public function getRutToken()
    {
        return $this->rutToken;
    }

    /**
     * Set rutToken value.
     *
     * @param string $rutToken
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\RespuestaTo
     */
    public function setRutToken($rutToken = null)
    {
        // validation for constraint: string
        if (null !== $rutToken && !\is_string($rutToken)) {
            throw new \InvalidArgumentException(\sprintf('Invalid value %s, please provide a string, %s given', \var_export($rutToken, true), \gettype($rutToken)), __LINE__);
        }
        $this->rutToken = $rutToken;

        return $this;
    }
}
