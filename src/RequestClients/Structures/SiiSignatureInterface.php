<?php

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use DOMDocument;

interface SiiSignatureInterface
{
    /**
     * @param \DOMDocument $doc              el documento o request a firmar
     * @param mixed        $referenceId      opcional  atributo ID del nodo firmado
     * @param null|string  $referenceTagName tagname del nodo firmado
     * @param mixed        $xmlns_xsi        cuando es true, la firma incluye schemaNameInstance
     *
     * @return string doc firmado
     */
    public function signXMLDoc(DOMDocument $doc, $referenceId = '', $referenceTagName = null, $xmlns_xsi = false): string;

    /**
     * Gets the extra certificates (CA) if any.
     */
    public function getExtraCerts(): ?array;

    /**
     * Gets the certificate.
     *
     * @param bool $trimPrefixAndSuffix para quitar '-----BEGIN CERTIFICATE-----' y '-----END CERTIFICATE-----'
     *
     * @return string Llave pública de la firma
     */
    public function getPublicKey($trimPrefixAndSuffix = false): string;

    /**
     * Gets the certificate.
     *
     * @param bool $trimPrefixAndSuffix para quitar '-----BEGIN PRIVATE KEY-----' y '-----END PRIVATE KEY-----'
     *
     * @return string Llave private de la firma
     */
    public function getPrivateKey($trimPrefixAndSuffix = false): string;

    public function getCerts(): CertificatesObjectInterface;
}
