<?php

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Helpers;

use CTOhm\SiiAsyncClients\Util\ExceptionHelper;

/**
 * This interface describes the structure of the response
 * expected when calling
 * - checkFirma
 * - verifyXML
 * - a signature validation response.
 *
 * @XmlRoot("Signature")
 */
class SignatureNode implements \JsonSerializable
{
    /**
     * { var_description }.
     *
     * @var SignedInfo
     * @Annotation\Type("CTOhm\SiiAsyncClients\RequestClients\XMLDsig\SignedInfo")
     */
    public $SignedInfo;

    /**
     * { var_description }.
     *
     * @var string
     * @Annotation\Type("string")
     */
    public $SignatureValue;

    /**
     * { var_description }.
     *
     * @var array<array-key, mixed>
     * @Annotation\Type("array")
     */
    public $KeyInfo = [];

    /**
     * @var string
     */
    private $referenceId;

    /**
     * @var string
     */
    private $modulus;

    /**
     * @var string
     */
    private $exponent;

    /**
     * @var string
     */
    private $X509Certificate;

    /**
     * @var \DOMDocument
     */
    private $ownerDocument;

    /**
     * @var \DOMNode
     */
    private $DOMElement;

    /**
     * Constructs a new instance.
     *
     * @param bool   $xmlns_xsi   The xmlns xsi
     * @param string $referenceId The referenced node id
     */
    public function __construct(bool $xmlns_xsi = false, $referenceId = '')
    {
        $this->referenceId = $referenceId;
        $this->SignedInfo = new SignedInfo($xmlns_xsi, $referenceId);
        $this->KeyInfo = $this->getKeyInfo();
        //$this->SignatureValue = [];
    }

    /**
     * @return string[][]
     *
     * @psalm-return array{RSAKeyValue: array{Modulus: string, Exponent: string}}
     */
    public function getKeyValue(): array
    {
        return [
            'RSAKeyValue' => [
                'Modulus' => $this->modulus,
                'Exponent' => $this->exponent,
            ],
        ];
    }

    /**
     * Gets the key information.
     *
     * @return (string|string[])[][] the key information
     *
     * @psalm-return array{KeyValue: array{RSAKeyValue: array{Modulus: string, Exponent: string}}, X509Data: array{X509Certificate: string}}
     */
    public function getKeyInfo()
    {
        return [
            'KeyValue' => $this->getKeyValue(),
            'X509Data' => [
                'X509Certificate' => $this->X509Certificate,
            ],
        ];
    }

    /**
     * Sets the first element matching a tag name.
     *
     * @param \DOMDocument|\DOMElement $element       The element
     * @param array<string, scalar>    $tagsAndValues The tag name
     *
     * @return \DOMDocument|\DOMElement ( description_of_the_return_value )
     */
    public static function setElement0ByTagName($element, array $tagsAndValues)
    {
        foreach ($tagsAndValues as $tagName => $newValue) {
            $element->getElementsByTagName($tagName)->item(0)->nodeValue = $newValue;
        }

        return $element;
    }

    /**
     * @return static
     */
    public function setModulus(string $modulus): self
    {
        $this->modulus = $modulus;
        $this->getDOMDocument()->getElementsByTagName('Modulus')->item(0)->nodeValue = $modulus;

        return $this;
    }

    /**
     * @return static
     */
    public function setExponent(string $exponent): self
    {
        $this->exponent = $exponent;
        $this->getDOMDocument()->getElementsByTagName('Exponent')->item(0)->nodeValue = $exponent;

        return $this;
    }

    /**
     * @return static
     */
    public function setX509Certificate(string $X509Certificate): self
    {
        $this->X509Certificate = $X509Certificate;
        $this->getDOMDocument()->getElementsByTagName('X509Certificate')->item(0)->nodeValue = $X509Certificate;

        return $this;
    }

    /**
     * @return static
     */
    public function setSignatureValue(string $SignatureValue): self
    {
        $this->SignatureValue = $SignatureValue;
        $this->getDOMDocument()->getElementsByTagName('SignatureValue')->item(0)->nodeValue = $SignatureValue;

        return $this;
    }

    public function getDOMDocument(?\DOMDocument $doc = null): \DOMElement
    {
        if (null === $this->DOMElement) {
            $this->ownerDocument = $this->ownerDocument ?? $doc;
            $this->DOMElement = $this->ownerDocument->importNode(
                (new SiiDOMDocument())->generate($this->jsonSerialize())->documentElement,
                true
            );
        }

        return $this->DOMElement;
    }

    /**
     * @return false|string
     */
    public function computeSignedInfo(?string $referenceTagName = null)
    {
        $Signature = $this->getDOMDocument();
        // crear nodo para la firma

        // default reference node
        $referenceNode = $this->ownerDocument;

        if ($referenceTagName) {
            $referenceNode = $this->getReferenceNode($referenceTagName);

            if (!$referenceNode) {
                throw new \InvalidArgumentException(ExceptionHelper::get(ExceptionHelper::NODE_NOT_REACHABLE, [
                    $referenceTagName,
                ]));
            }
            $referenceIdAttribute = $referenceNode->getAttribute('ID');

            if ($this->referenceId && \sprintf('#%s', $referenceIdAttribute ?? '') !== $this->referenceId) {
                throw new \InvalidArgumentException(
                    ExceptionHelper::get(
                        ExceptionHelper::MISMATCHED_SIGNATURE_REFERENCE,
                        [
                            $this->referenceId, $referenceTagName, $referenceIdAttribute,
                        ]
                    )
                );
            }
        }
        $c14nReferenceContent = $referenceNode->C14N();

        $DigestValue = \base64_encode(\sha1($c14nReferenceContent, true));
        self::setElement0ByTagName($Signature, ['DigestValue' => $DigestValue]);

        return $this->ownerDocument->saveHTML($Signature->getElementsByTagName('SignedInfo')->item(0));
    }

    /**
     * Sets the owner document.
     *
     * @param SiiDOMDocument $ownerDocument The owner document
     *
     * @throws \InvalidArgumentException (description)
     *
     * @return static ( description_of_the_return_value )
     */
    public function setOwnerDocument(SiiDOMDocument $ownerDocument): self
    {
        if (!$ownerDocument->documentElement) {
            throw new \InvalidArgumentException(ExceptionHelper::get(ExceptionHelper::DOCUMENT_IS_DAMAGED_OR_INVALID));
        }
        $this->ownerDocument = $ownerDocument;

        return $this;
    }

    /**
     * @return (array|mixed|string)[][]
     *
     * @psalm-return array{Signature: array{@attributes: array{xmlns: string}, SignedInfo: mixed, SignatureValue: string, KeyInfo: array}}
     */
    public function toArray(): array
    {
        return ['Signature' => [
            '@attributes' => [
                'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
            ],
            'SignedInfo' => $this->SignedInfo->jsonSerialize(),
            'SignatureValue' => $this->SignatureValue,
            'KeyInfo' => $this->getKeyInfo(),
        ]];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param string $referenceTagName
     */
    private function getReferenceNode($referenceTagName): ?\DOMElement //:\DOMElement
    {
        return $this->ownerDocument->documentElement->getElementsByTagName($referenceTagName)->item(0);
    }
}
