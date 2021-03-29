<?php

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Helpers;

use CTOhm\SiiAsyncClients\RequestClients\Structures\CertificatesObjectInterface;
use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use Tests\Helpers\SignatureNode;
use Tests\Helpers\CertificatesObject;
use DOMDocument;

/**
 * Class SiiSignature.
 */
class SiiSignature implements SiiSignatureInterface
{
    const WORDWRAP = 64;

    public $id;

    private $uuid;

    /**
     * @var array
     */
    private $certs;

    /**
     * @var array|false
     */
    private $data;

    private $ca;

    /**
     * @var false|string
     */
    private $pfx_contents;

    private $pfx_pass;

    private $public_key;

    private $private_key;

    public function __construct(object $pfxData, $wordwrap = self::WORDWRAP)
    {
        $this->pfx_contents = \base64_decode($pfxData->content_b64, true);

        $this->pfx_pass = decrypt($pfxData->password_val);
        // leer datos de la firma electrónica
        if (false === \openssl_pkcs12_read($this->pfx_contents, $this->certs, $this->pfx_pass)) {
            throw new \Exception('El certificado no pudo leerse como pkcs12 o el password es incorrecto');
        }

        $this->private_key = $this->certs['pkey'];
        $this->public_key = $this->certs['cert'];
        $this->ca = $this->certs['extracerts'] ?? null;

        $this->data = \openssl_x509_parse($this->public_key);
    }

    public function getCerts(): CertificatesObjectInterface
    {
        return new CertificatesObject($this->certs);
    }

    /**
     * Gets the extra certificates (CA) if any.
     */
    public function getExtraCerts(): ?array
    {
        return $this->ca;
    }

    /**
     * Gets the certificate.
     *
     * @param bool $trimPrefixAndSuffix para quitar '-----BEGIN CERTIFICATE-----' y '-----END CERTIFICATE-----'
     *
     * @return string Llave pública de la firma
     */
    public function getPublicKey($trimPrefixAndSuffix = false): string
    {
        if (!$trimPrefixAndSuffix) {
            return $this->public_key;
        }

        return \trim(\str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'],
            '',
            $this->public_key
        ));
    }

    /**
     * Gets the certificate.
     *
     * @param bool $trimPrefixAndSuffix para quitar '-----BEGIN PRIVATE KEY-----' y '-----END PRIVATE KEY-----'
     *
     * @return string Llave private de la firma
     */
    public function getPrivateKey($trimPrefixAndSuffix = false): string
    {
        if (!$trimPrefixAndSuffix) {
            return $this->private_key;
        }

        return \trim(\str_replace(
            ['-----BEGIN PRIVATE KEY-----', '-----END PRIVATE KEY-----'],
            '',
            $this->private_key
        ));
    }

    /**
     * Gets the first element matching a tag name.
     *
     * @param \DomDocument|\DomElement $element The element
     * @param string                   $tagName The tag name
     *
     * @return null|string the element 0 by tag name
     */
    public static function getFirstNodeValue($element, string $tagName)
    {
        return $element->getElementsByTagName($tagName)->item(0)->nodeValue;
    }

    /**
     * Returns an array representation of the object.
     *
     * @return (false|mixed|string)[] array representation of the object
     *
     * @psalm-return array{id: string, subject: mixed, valid_from: false|string, valid_to: false|string, issuer: mixed, hash: mixed, rut: string, email: mixed}
     */
    public function toArray()
    {
        $relevantdata = [
            'id' => $this->getID(),
            'subject' => $this->data['subject'],
            'valid_from' => $this->getFrom(),
            'valid_to' => $this->getTo(),
            'issuer' => $this->data['issuer'],
            'hash' => $this->data['hash'],
        ];

        $relevantdata['rut'] = $this->getID();
        $relevantdata['email'] = $relevantdata['subject']['emailAddress'];

        return $relevantdata;
    }

    /**
     * Obtiene RUT asociado al certificado.
     *
     * @return string RUT asociado al certificado
     */
    public function getID()
    {
        if (isset($this->data['subject']['serialNumber'])) {
            return \ltrim($this->data['subject']['serialNumber'], '0');
        }

        throw new \Exception('No fue posible obtener el RUT asociado al certificado');
    }

    /**
     * entrega el CN del subject.
     *
     * @throws \Exception (description)
     *
     * @return string CN del subject
     */
    public function getName()
    {
        if (isset($this->data['subject']['CN'])) {
            return $this->data['subject']['CN'];
        }

        throw new \Exception('No fue posible obtener el Common Name');
    }

    /**
     * entrega el emailAddress del subject.
     *
     * @return string correo  del subject
     */
    public function getEmail()
    {
        if (isset($this->data['subject']['emailAddress'])) {
            return $this->data['subject']['emailAddress'];
        }

        throw new \Exception('No se encontró un correo asociado al certificado digital');
    }

    /**
     * entrega desde cuando es válida la firma.
     *
     * @return false|string fecha inicio validez de la firma
     */
    public function getFrom()
    {
        return \date('Y-m-d H:i:s', $this->data['validFrom_time_t']);
    }

    /**
     * entrega hasta cuando es válida la firma.
     *
     * @return false|string fecha fim validez de la firma
     */
    public function getTo()
    {
        return \date('Y-m-d H:i:s', $this->data['validTo_time_t']);
    }

    /**
     * realizar la firma de datos.
     *
     * @param string $data          datos a firmat
     * @param mixed  $signature_alg Algoritmo que se utilizará para firmar (por defect SHA1)
     *
     * @return string string de la data firmada, o false
     */
    public function sign(string $data, $signature_alg = \OPENSSL_ALGO_SHA1)
    {
        $signature = null;

        if (false === \openssl_sign($data, $signature, $this->private_key, $signature_alg)) {
            throw new \Exception('No se pudo firmar los datos: Firma o datos son inválidos?');
        }

        return \base64_encode($signature);
    }

    public static function staticVerify(
        $data,
        $signature,
        $pub_key,
        $signature_alg = \OPENSSL_ALGO_SHA1
    ) {
        $normalized = \trim(\str_replace(
            [PHP_EOL, '-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'],
            '',
            $pub_key
        ));
        $pub_key = self::normalizePublicKey(
            $normalized
        );
        //dump($pub_key);
        return 1 === \openssl_verify($data, \base64_decode($signature, true), $pub_key, $signature_alg) ? true : false;
    }

    /**
     * Check if document's signature matches this signature instance.
     *
     * @param string $xml_data The xml data
     * @param null|string $tag      The tag
     * @param bool   $full     if true, return array of results
     * @param mixed  $verbose
     *
     * @return array{0: bool, 1: bool, 2: false|string, 3: string}
     */
    public static function verifyXMLVerbose(
        $xml_data,
        $tag = null,
        $verbose = false
    ) {
        $doc = new DOMDocument();
        $doc->loadXML($xml_data);

        // preparar datos que se verificarán
        $SignaturesElements = $doc->documentElement->getElementsByTagName('Signature');
        $Signature = $doc->documentElement->removeChild(
            $SignaturesElements->item(
                $SignaturesElements->length - 1
            )
        );

        $SignedInfo = $Signature->getElementsByTagName('SignedInfo')->item(0);
        $SignedInfo->setAttribute('xmlns', $Signature->getAttribute('xmlns'));
        $signed_info = $doc->saveHTML($SignedInfo);

        $SignatureValue = self::getFirstNodeValue(
            $Signature,
            'SignatureValue'
        );
        $SignatureValue = \str_replace([' ', \PHP_EOL], '', $SignatureValue);
        $pub_key = self::getFirstNodeValue(
            $Signature,
            'X509Certificate'
        );
        $pub_key = \str_replace([' ', \PHP_EOL], '', $pub_key);

        $DigestValue = self::getFirstNodeValue(
            $Signature,
            'DigestValue'
        );

        $valid_public_key = self::staticVerify(
            $signed_info,
            $SignatureValue,
            $pub_key
        );

        if ($tag) {
            $elementTag = $doc->documentElement->getElementsByTagName($tag);
            $C14NdataToBeSigned = $elementTag->item(0)->C14N();
        } else {
            $C14NdataToBeSigned = $doc->C14N();
        }
        $ComputedDigest = \base64_encode(\sha1($C14NdataToBeSigned, true));

        $matching_digests = $DigestValue === $ComputedDigest;

        return [
            'valid_public_key' => $valid_public_key,
            'matching_digests' => $matching_digests,

        ];
    }
    /**
     * verifica la firma digital de datos.
     *
     * @param string      $data          The data
     * @param string      $signature     The signature
     * @param null|string $pub_key       The pub key
     * @param int         $signature_alg The signature alg
     *
     * @return bool true en caso de éxito, false si no
     */
    public function verify(
        $data,
        $signature,
        $pub_key = null,
        $signature_alg = \OPENSSL_ALGO_SHA1
    ) {
        if (null === $pub_key) {
            $pub_key = $this->public_key;
        }

        $pub_key = $this->normalizePublicKey($pub_key);
        //dump($pub_key);
        return 1 === \openssl_verify($data, \base64_decode($signature, true), $pub_key, $signature_alg) ? true : false;
    }

    /**
     * @param null|string $referenceTagName
     * @param mixed       $referenceId
     * @param mixed       $xmlns_xsi
     */
    public function signXMLDoc(DOMDocument $doc, $referenceId = '', $referenceTagName = null, $xmlns_xsi = false): string
    {
        if (\is_string($referenceId) && 0 !== \mb_strpos($referenceId, '#')) {
            $referenceId = '#' . $referenceId;
        }
        $tempDocCopy = new DOMDocument();
        $tempDocCopy->loadXML($doc->saveXML());

        $signatureNode = new SignatureNode($xmlns_xsi, $referenceId);

        /**
         * This is the info that will be signed in the next step.
         *
         * @var string
         */
        $SignedInfo = $signatureNode
            ->setOwnerDocument($tempDocCopy)
            ->computeSignedInfo($referenceTagName);
        //kdump($SignedInfo);
        $SignatureValue = \wordwrap($this->sign($SignedInfo), self::WORDWRAP, "\n", true);

        $Signature = $signatureNode
            ->setSignatureValue($SignatureValue)
            ->setModulus($this->getModulus())
            ->setExponent($this->getExponent())
            ->setX509Certificate($this->getPublicKey(true))
            ->getDomDocument();

        //return $signatureNode->appendNodeToDocument();

        $tempDocCopy->documentElement->appendChild($Signature);

        return $tempDocCopy->saveXML();
        // kdump($signedDoc);
    }

    /**
     * Gets the modulus.
     *
     * @return string base64 modulus
     */
    public function getModulus()
    {
        $details = \openssl_pkey_get_details(\openssl_pkey_get_private($this->private_key));

        return \wordwrap(\base64_encode($details['rsa']['n']), self::WORDWRAP, "\n", true);
    }

    /**
     * Gets the exponent.
     *
     * @return string the exponent
     */
    public function getExponent()
    {
        $details = \openssl_pkey_get_details(\openssl_pkey_get_private($this->private_key));

        return \wordwrap(\base64_encode($details['rsa']['e']), self::WORDWRAP, "\n", true);
    }

    /**
     * agrega el inicio y fin de un certificado (clave pública).
     *
     * @param string $cert Certificado a normalizar
     *
     * @return string Certificado normalizado
     */
    public static function normalizePublicKey($cert)
    {
        if (false === \mb_strpos($cert, '-----BEGIN CERTIFICATE-----')) {
            $body = \trim($cert);
            $cert = '-----BEGIN CERTIFICATE-----' . "\n";
            $cert .= \wordwrap($body, self::WORDWRAP, "\n", true) . "\n";
            $cert .= '-----END CERTIFICATE-----' . "\n";
        }

        return $cert;
    }
}
