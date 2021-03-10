<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte;

use WsdlToPhp\PackageBase\AbstractStructBase;

/**
 * This class stands for DteEventoDocTo StructType.
 */
final class DteEventoDocTo extends AbstractStructBase
{
    /**
     * The codEvento
     * Meta information extracted from the WSDL
     * - minOccurs: 0.
     *
     * @var string
     */
    public $codEvento;

    /**
     * The descEvento
     * Meta information extracted from the WSDL
     * - minOccurs: 0.
     *
     * @var string
     */
    public $descEvento;

    /**
     * The rutResponsable
     * Meta information extracted from the WSDL
     * - minOccurs: 0.
     *
     * @var string
     */
    public $rutResponsable;

    /**
     * The dvResponsable
     * Meta information extracted from the WSDL
     * - minOccurs: 0.
     *
     * @var string
     */
    public $dvResponsable;

    /**
     * The fechaEvento
     * Meta information extracted from the WSDL
     * - minOccurs: 0.
     *
     * @var string
     */
    public $fechaEvento;

    /**
     * Constructor method for DteEventoDocTo.
     *
     * @uses DteEventoDocTo::setCodEvento()
     * @uses DteEventoDocTo::setDescEvento()
     * @uses DteEventoDocTo::setDvResponsable()
     * @uses DteEventoDocTo::setFechaEvento()
     * @uses DteEventoDocTo::setRutResponsable()
     *
     * @param string $codEvento
     * @param string $descEvento
     * @param string $rutResponsable
     * @param string $dvResponsable
     * @param string $fechaEvento
     */
    public function __construct($codEvento = null, $descEvento = null, $rutResponsable = null, $dvResponsable = null, $fechaEvento = null)
    {
        $this
            ->setCodEvento($codEvento)
            ->setDescEvento($descEvento)
            ->setRutResponsable($rutResponsable)
            ->setDvResponsable($dvResponsable)
            ->setFechaEvento($fechaEvento);
    }

    /**
     * @return (null|string)[]
     *
     * @psalm-return array{codEvento: null|string, descEvento: null|string, rutResponsable: string, fechaEvento: null|string}
     */
    public function jsonSerialize(): array
    {
        return [
            'codEvento' => $this->getCodEvento(),
            'descEvento' => $this->getDescEvento(),
            'rutResponsable' => \sprintf('%s-%s', $this->getRutResponsable(), $this->getDvResponsable()),
            'fechaEvento' => $this->getFechaEvento(),
        ];
    }

    /**
     * Get codEvento value.
     *
     * @return string
     */
    public function getCodEvento()
    {
        return $this->codEvento;
    }

    /**
     * Set codEvento value.
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo
     */
    public function setCodEvento(?string $codEvento = null): self
    {
        $this->codEvento = $codEvento;

        return $this;
    }

    /**
     * Get descEvento value.
     *
     * @return string
     */
    public function getDescEvento()
    {
        return $this->descEvento;
    }

    /**
     * Set descEvento value.
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo
     */
    public function setDescEvento(?string $descEvento = null): self
    {
        $this->descEvento = $descEvento;

        return $this;
    }

    /**
     * Get rutResponsable value.
     *
     * @return string
     */
    public function getRutResponsable()
    {
        return $this->rutResponsable;
    }

    /**
     * Set rutResponsable value.
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo
     */
    public function setRutResponsable(?string $rutResponsable = null): self
    {
        $this->rutResponsable = $rutResponsable;

        return $this;
    }

    /**
     * Get dvResponsable value.
     *
     * @return string
     */
    public function getDvResponsable()
    {
        return $this->dvResponsable;
    }

    /**
     * Set dvResponsable value.
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo
     */
    public function setDvResponsable(?string $dvResponsable = null): self
    {
        $this->dvResponsable = $dvResponsable;

        return $this;
    }

    /**
     * Get fechaEvento value.
     *
     * @return string
     */
    public function getFechaEvento()
    {
        return $this->fechaEvento;
    }

    /**
     * Set fechaEvento value.
     *
     * @return \CTOhm\SiiAsyncClients\Wsdl\WsRegistroReclamoDte\DteEventoDocTo
     */
    public function setFechaEvento(?string $fechaEvento = null): self
    {
        $this->fechaEvento = $fechaEvento;

        return $this;
    }
}
