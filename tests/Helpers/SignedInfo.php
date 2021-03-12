<?php

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Helpers;

/**
 * This interface describes the structure of the response
 * expected when calling
 * - checkFirma
 * - verifyXML
 * - a signature validation response.
 *
 * @XmlRoot("Signature")
 */
class SignedInfo implements \JsonSerializable
{
    /**
     * @Annotation\Exclude
     *
     * @var bool
     */
    private $xmlns_xsi = false;

    /**
     * Constructs a new instance.
     *
     * @param bool   $xmlns_xsi The xmlns xsi
     * @param string $reference The reference
     */
    public function __construct(bool $xmlns_xsi = false, $reference = '')
    {
        $this->xmlns_xsi = $xmlns_xsi;
        $this->reference = $reference;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return (((string|string[])[]|mixed|string)[]|false|null|string)[][]
     *
     * @psalm-return array{@attributes: array{xmlns: string, 'xmlns:xsi': false|string}, CanonicalizationMethod: array{@attributes: array{Algorithm: string}}, SignatureMethod: array{@attributes: array{Algorithm: string}}, Reference: array{@attributes: array{URI: mixed}, Transforms: array{Transform: array{@attributes: array{Algorithm: string}}}, DigestMethod: array{@attributes: array{Algorithm: string}}, DigestValue: null}}
     */
    private function toArray(): array
    {
        return [
            '@attributes' => [
                'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
                'xmlns:xsi' => $this->xmlns_xsi ? 'http://www.w3.org/2001/XMLSchema-instance' : false,
            ],
            'CanonicalizationMethod' => [
                '@attributes' => [
                    'Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                ],
            ],
            'SignatureMethod' => [
                '@attributes' => [
                    'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                ],
            ],
            'Reference' => [
                '@attributes' => [
                    'URI' => $this->reference,
                ],
                'Transforms' => [
                    'Transform' => [
                        '@attributes' => [
                            'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
                        ],
                    ],
                ],
                'DigestMethod' => [
                    '@attributes' => [
                        'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                    ],
                ],
                'DigestValue' => null,
            ],
        ];
    }
}
