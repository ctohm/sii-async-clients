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
     */
    public function getCodResp(): ?int
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
    public function setCodResp(?int $codResp = null): self
    {
        $this->codResp = $codResp;

        return $this;
    }

    /**
     * Get descResp value.
     */
    public function getDescResp(): ?string
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
    public function setDescResp(?string $descResp = null): self
    {
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
    public function getListaEventosDoc(): ?array
    {
        return $this->listaEventosDoc ?? null;
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
        $listaEventosDoc = \array_filter($listaEventosDoc, static fn ($item) => $item instanceof DteEventoDocTo);

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
    public function setRutToken(?string $rutToken = null)
    {
        $this->rutToken = $rutToken;

        return $this;
    }
}
