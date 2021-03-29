<?php

/**
 * DBThor Cesion 1.11.0
 */

namespace Tests\Helpers;

use CTOhm\SiiAsyncClients\Util\Misc;


use DOMElement;

use DOMNode;
use DOMXPath;


use Illuminate\Support\Str;
use Ramsey\Uuid\Exception\NodeException;
use stdClass;

/*
 * @see https://www.php.net/manual/en/function.libxml-get-errors.php
 */

libxml_use_internal_errors(true);
/**
 * @/extends DomDocument
 */
/**
 * @extends DOMDocument
 * @property-read string $initialXML
 */
class SiiXML extends \DOMDocument
{
    const VERSION = '1.0';
    const ENCODING = 'ISO-8859-1';
    const NO_FORMAT_OUTPUT = false;
    const FORMAT_OUTPUT = true;

    const PRESERVE_WHITE_SPACE = true;
    const NO_PRESERVE_WHITE_SPACE = false;
    public ?SiiDocumentValidationResult   $validationResult = null;
    public const SII_SIGNATURE_NAMESPACE = 'http://www.w3.org/2000/09/xmldsig#';


    public $formatOutput;

    /**
     * @readonly
     * @psalm-allow-private-mutation
     */
    private string $initialXML = '';

    //private $doc_type;
    //public $version;
    //public $encoding;
    public ?Documento $parentSiiDocument = null;

    /**
     * Constructs a new instance.
     *
     * @param string   $version       The version number of the document as part of the XML declaration.
     * @param string   $encoding      Internal encoding for the XML parsing handler
     * @param bool  $formatOutput  The format output
     */
    public function __construct(
        string $version = self::VERSION,
        string $encoding = self::ENCODING,
        bool $formatOutput = self::NO_FORMAT_OUTPUT,
        bool $preserveWhiteSpace = self::PRESERVE_WHITE_SPACE
    ) {
        parent::__construct(
            $version,
            $encoding
        );
        $this->formatOutput = $formatOutput;
        $this->preserveWhiteSpace = $preserveWhiteSpace;
    }

    public function getInitialXML(): string
    {
        return $this->initialXML ?? $this->saveXML();
    }

    public function setParentSiiDocument(Documento $siiDocument)
    {
        $this->parentSiiDocument = $siiDocument;
    }

    /**
     * { function_description }.
     *
     * @param array<array-key, mixed>       $data       The data
     * @param array<array-key, mixed>|null       $namespace  The namespace to generate the XML (URI and prefix)
     * @param \DOMElement|null  $parent     parent element of the XML. Null if we want to generate the root
     *
     * @return self
     */
    public function generate(array $data, array $namespace = null, \DOMElement &$parent = null)
    {
        if ($namespace !== null) {
            throw new \InvalidArgumentException('Namespaces are not suported ' . json_encode($namespace));
        }
        if ($parent === null) {
            $parent = &$this;
        }
        foreach ($data as $key => $value) {
            if ($key == '@attributes') {
                if ($value !== false) {
                    foreach ($value as $attr => $val) {
                        if ($val !== false) {
                            $parent->setAttribute(
                                $attr,
                                $val
                            );
                        }
                    }
                }
            } elseif ($key == '@value') {
                $parent->nodeValue = EncodingHelper::entitiesToChars($value);
            } else {
                if (is_array($value)) {
                    if (!empty($value)) {
                        $keys = array_keys($value);
                        if (!is_int($keys[0])) {
                            $value = [
                                $value,
                            ];
                        }
                        foreach ($value as $value2) {
                            if ($namespace) {
                                $Node = $this->createElementNS($namespace[0], $namespace[1] . ':' . $key);
                            } else {
                                $Node = $this->createElement($key);
                            }
                            $parent->appendChild($Node);
                            if (!is_array($value2)) {
                                kdump($value2);
                            }
                            $this->generate(
                                $value2,
                                $namespace,
                                $Node
                            );
                        }
                    }
                } else {
                    if (is_object($value) && $value instanceof \DOMElement) {
                        $Node = $this->importNode(
                            $value,
                            true
                        );
                        $parent->appendChild($Node);
                    } else {
                        if ($value !== false) {
                            if ($namespace) {
                                $Node = $this->createElementNS($namespace[0], $namespace[1] . ':' . $key, EncodingHelper::iso2utf8(EncodingHelper::entitiesToChars($value)));
                            } else {
                                $Node = $this->createElement($key, EncodingHelper::iso2utf8(EncodingHelper::entitiesToChars($value)));
                            }
                            $parent->appendChild($Node);
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Sets a new value for the first element matching a tag name.
     *
     * @param string                $tagName   The tag name
     * @param mixed   $newValue  the new value
     *
     * @return self  ( description_of_the_return_value )
     */
    public function setValueOfFirstMatchingTag(string $tagName, $newValue): self
    {
        $this->getElementsByTagName($tagName)->item(0)->nodeValue = $newValue;

        return $this;
    }

    /**
     * Carga un string XML en el Objeto desde un string.
     * @see https://www.php.net/manual/en/domdocument.loadxml.php
     *
     * @param string   $source   The source
     * @param int|null  $options
     *
     * @return \DomDocument|bool  ( description_of_the_return_value )
     */
    public function loadXML(
        $source,
        $options = null
    ) {
        if (!$source) {
            return false;
        }
        //kdiff(EncodingHelper::iso2utf8($source), EncodingHelper::iso2utf8($source, true), 'loadXML forcing utf8');
        $this->initialXML = EncodingHelper::iso2utf8($source);
        $xml = parent::loadXML($this->initialXML, $options);

        foreach ($this->childNodes as $child) {
            if ($child->nodeType === \XML_DOCUMENT_TYPE_NODE) {
                $this->removeChild($child);
                break;
            }
        }

        return $xml;
    }

    public function hasValidPublicKey(bool $throw = false): bool
    {
        $xmlDsigAdapter = new XmlDsigAdapter($this);

        return $xmlDsigAdapter->verifyValidPublicKey($throw);
    }

    public function xmlDsigVerify(bool $throw = false): bool
    {
        $xmlDsigAdapter = new XmlDsigAdapter($this);

        return $xmlDsigAdapter->verifyXML($throw);
    }

    public function hasMatchingDigest(bool $throw = false): bool
    {
        $xmlDsigAdapter = new XmlDsigAdapter($this);

        return $xmlDsigAdapter->verifyMatchingDigest($throw);
    }
    /**
     * Wrapper to classic Xpath queries on a DOMXpath object, just saving us the constuctor boilerplate.
     *
     * @param string expression  $expression  XPath to execute
     *
     * @return \DOMNodeList
     */
    public function xpath(
        $expression,
        ?DOMNode $contextNode = null,
        bool $registerNodeNS = true
    ) {
        return (new \DOMXPath($this))->query($expression, $contextNode, $registerNodeNS);
    }

    /**
     * entrega el código XML aplanado y con la codificación que corresponde.
     *
     * @param null|string  $xpath  The xpath to extract
     *
     * @return false|string flattened xml as string
     */
    public function getFlattened(
        $xpath = null,
        ?DOMNode $contextNode = null,
        bool $registerNodeNS = true
    ) {
        if ($xpath) {
            $node = $this->xpath($xpath, $contextNode, $registerNodeNS)->item(0);
            if (!$node) {
                return false;
            }

            $xml = EncodingHelper::utf2iso($node->C14N());
            $xml = self::normalizeQuotes($xml);
        } else {
            $xml = $this->C14N();
        }
        $xml = preg_replace("/\>\n\s+\</", '><', $xml);
        $xml = preg_replace("/\>\n\t+\</", '><', $xml);
        $xml = preg_replace("/\>\n+\</", '><', $xml);

        return trim($xml);
    }

    /**
     * @return int Cantidad de hijos directos de $dom llamados "tagName"
     */
    private function countTwins(\DOMElement $dom, $tagName)
    {
        $twins = 0;
        foreach ($dom->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->tagName == $tagName) {
                $twins++;
            }
        }

        return $twins;
    }

    /**
     * Returns the internal libxml errors.
     *
     * @param bool  $full  Include the whole exception stack
     *
     * @return \Illuminate\Support\Collection an array of libxmlerrors or normalized messages
     */
    public function getErrors(
        $full = false
    ) {
        $errors = [];
        foreach (libxml_get_errors() as $e) {
            $exception = new XmlException($e);
            $errors[] = $full ? $exception : ExceptionHelper::normalizeException($exception);
        }

        libxml_clear_errors();

        return collect($errors);
    }

    /**
     * entrega el nombre del tag raíz del XML.
     * @return string
     */
    public function getName()
    {
        return $this->documentElement->tagName;
    }

    /**
     * file location of the XSD schema.
     * @return string|null
     */
    public function getSchema(): ?string
    {
        $schemaLocation = $this->documentElement->getAttribute('xsi:schemaLocation');
        if (!$schemaLocation || strpos($schemaLocation, ' ') === false) {
            return null;
        }

        list($uri, $xsd) = explode(' ', $schemaLocation);

        return $xsd;
    }

    /**
     * Wrapper for saveXML(), fixing entities as per SII requirements.
     *
     * @param \DOMNode|null  $node     The node
     * @param int|null       $options  The options (Sólo LIBXML_NOEMPTYTAG está soportado)
     *
     * @return string
     */
    public function saveXML(\DOMNode $node = null, $options = null): string
    {
        $original_xml = parent::saveXML(
            $node,
            $options
        );
        /*$original_xml = EncodingHelper::getInstance(parent::saveXML($node, $options))->toUTF8()
        ->withoutXMLDeclaration()
        ->decodeEntities()
        ->withoutComments()
        ->__toString();*/
        $fixed_entities_xml = self::normalizeQuotes($original_xml);

        return $fixed_entities_xml;
    }

    /**
     * Wrapper for C14N() fixing entities as per SII requirements.
     *
     * @param bool|null   $exclusive      Retain only the nodes matched by the xpath or ns prefixes
     * @param bool|null   $with_comments  Retains comments
     * @param array<array-key, mixed>|null  $xpath          The xpath to filter by
     * @param mixed|null  $ns_prefixes    The ns prefixes to filter by
     *
     * @return string
     */
    public function C14N(
        $exclusive = null,
        $with_comments = null,
        array $xpath = null,
        $ns_prefixes = null
    ) {
        $original_xml = parent::C14N(
            $exclusive,
            $with_comments,
            $xpath,
            $ns_prefixes
        );
        if (!EncodingHelper::isUtf($original_xml)) {
            //dump($original_xml);
            debuglog()->error(new \Exception('not utf8 valid!'));
        }
        $fixed_entities_xml = self::normalizeQuotes($original_xml);

        return $fixed_entities_xml;
    }

    /**
     * convierte el XML a un Array.
     *
     * @param \DOMElement|null  $dom           The dom
     * @param array<array-key, mixed>|null        $xmlArray      The xml array
     * @param bool    $ArrayNodos  pass true to treat the input as an array (therefore return a parallel array of nodes)
     *
     * @return (((array|mixed)[]|mixed|string)[]|mixed|null|string)[]|false|null
     *
     * @psalm-return array<array-key, array<string, mixed|non-empty-list<array<array-key, mixed>|mixed>|string>|mixed|null|string>|false|null
     */
    public function toArray(\DOMElement $dom = null, array &$xmlArray = null, $ArrayNodos = false)
    {
        // determinar valores de parámetros
        if (!$dom) {
            $dom = $this->documentElement;
        }
        if (!$dom) {
            return false;
        }
        $tagName = $dom->tagName;
        if ($xmlArray === null) {
            $xmlArray = [
                $tagName => null,
            ];
        }
        // agregar atributos del nodo
        if ($dom->hasAttributes()) {
            $xmlArray[$tagName]['@attributes'] = [];
            foreach ($dom->attributes as $attribute) {
                $xmlArray[$tagName]['@attributes'][$attribute->name] = $attribute->value;
            }
        }
        // si existen nodos hijos se agregan
        if (!$dom->hasChildNodes()) {
            return $xmlArray;
        }
        foreach ($dom->childNodes as $child) {
            if ($child instanceof \DOMText &&
                $textContent = trim($child->textContent)
            ) {
                if ($dom->childNodes->length == 1
                    && empty($xmlArray[$tagName])
                ) {
                    $xmlArray[$tagName] = $textContent;
                } else {
                    //kdump(['@value'=>['tagName'=> $tagName,'xmlArray'=> $xmlArray,'$textContent'=>$textContent]]);

                    $xmlArray[$tagName]['@value'] = $textContent;
                }
            } elseif ($child instanceof \DOMElement && $childTagName = $child->tagName) {
                // agregar nodo hijo directamente, ya que es el único nodo hijo con el mismo nombre de tag
                $twins = $this->countTwins(
                    $dom,
                    $childTagName
                );
                if ($twins == 1) {
                    if ($ArrayNodos) {
                        $this->toArray(
                            $child,
                            $xmlArray
                        );
                    } else {
                        $this->toArray(
                            $child,
                            $xmlArray[$tagName]
                        );
                    }
                } else {
                    $childArray = [];
                    $xmlArray[$tagName][$childTagName] = $xmlArray[$tagName][$childTagName] ?? [];
                    $output = $this->toArray(
                        $child,
                        $childArray,
                        true
                    );
                    $xmlArray[$tagName][$childTagName][] = $output[$childTagName] ?? $output;
                }
            }
        }

        // entregar Array
        return $xmlArray;
    }

    /**
     * Alternative to "toArray" using a recursion instead of pass by reference.
     *
     * @param \DOMElement|null  $dom           The dom
     *
     * @return array<array-key, mixed>|bool|string  ( description_of_the_return_value )
     */
    public function dumpArray(\DOMElement $dom = null)
    {
        $xmlArray = [];
        $tagName = '';
        $is_root = false;

        // Caso raíz. No se declaró el DOMElement
        if (!$dom) {
            // Si esta instancia no tiene un documento cargado, tampoco hay array
            if (!$this->documentElement || !$this->documentElement->tagName) {
                return false;
            }
            // Recursión: el array debe tener una única llave de entrada que es el tag raíz del documento
            return [
                $this->documentElement->tagName => $this->dumpArray($this->documentElement),
            ];
        }
        // agregar atributos del nodo
        if ($dom->hasAttributes()) {
            $xmlArray['@attributes'] = [];
            foreach ($dom->attributes as $attribute) {
                $xmlArray['@attributes'][$attribute->name] = $attribute->value;
            }
        }
        // Si no hay nodos hijos devolvemos el array
        if (!$dom->hasChildNodes()) {
            return $xmlArray;
        }

        foreach ($dom->childNodes as $child) {
            if (
                // Si este nodo hijo es texto
                $child instanceof \DOMText &&
                // Y tiene contenido
                $textContent = trim($child->textContent)
            ) {
                if ( // Si el $dom tiene un solo nodo texto
                    $dom->childNodes->length == 1
                    // Y el array está vacío (o sea no hay atributes)
                    && empty($xmlArray)
                ) {
                    // El resultado es el valor del texto es el valor del texto
                    return $textContent;
                } else {
                    // En otro caso para rehidratar se requiere meter el texto en una propiedad @value
                    $xmlArray['@value'] = $textContent;
                }
            } elseif ($child instanceof \DOMElement && $childTagName = $child->tagName) {
                // Si el array no tiene un nodo con el tagName del hijo, lo declaramos como array
                $xmlArray[$childTagName] = $xmlArray[$childTagName] ?? [];

                // El nodo hijo es un DOMElement. Su valor sale de la recursión sobre el presente método
                $xmlArray[$childTagName][] = $this->dumpArray($child);
            }
        }
        // No sé cómo podríamos llegar aquí pero por si acaso
        if (!is_array($xmlArray)) {
            return $xmlArray;
        }
        $childTags = [];
        foreach ($xmlArray as $childTagName => $childValue) {
            $debugPayload = [
                $childTagName => ['is_array' => is_array($childValue)],
            ];
            $childTags[] = $childTagName;

            // Si declaramos un hijo como array, pero no tiene siblings, se asigna su valor directamente
            if (is_array($childValue)) {
                $debugPayload[$childTagName]['count'] = count($childValue);
                if (count($childValue) == 1) {
                    $xmlArray[$childTagName] = $childValue[0] ?? $childValue;
                    $debugPayload[$childTagName]['childValue'] = ($xmlArray[$childTagName]);
                }
            }
        }

        if (empty($xmlArray)) {
            // Después de aplanar el array podría estar vacío?
            $xmlArray = '';
        }

        // entregar Array
        return $xmlArray;
    }

    public function toCollection(?DOMElement $doc = null)
    {
        return collect($this->dumpArray($doc));
    }

    /**
     * Fixes HTML entities ' (&apos;) y " (&quot;) as per SII requirements.
     *
     * @param string  $xml  The xml
     *
     * @return string
     */
    public static function normalizeQuotes(string $xml): string
    {
        $newXML = '';
        $n_letras = strlen($xml);

        $specialChars = [];
        $isTagOpen = false;

        $lastTag = '';
        for ($i = 0; $i < $n_letras; $i++) {
            if ($isTagOpen && $xml[$i] == '>') {
                $isTagOpen = false;
            }

            if (!$isTagOpen && $xml[$i] == '<') {
                $isTagOpen = true;
                $nextClosingChevron = mb_strpos(mb_substr($xml, $i), '>');
                if ($nextClosingChevron !== false) {
                    $lastTag = mb_substr(mb_substr($xml, $i), 0, $nextClosingChevron + 1);
                }
            }

            if (!$isTagOpen) {
                $l = $xml[$i] == '\'' ? '&apos;' : ($xml[$i] == '"' ? '&quot;' : $xml[$i]);
            } else {
                $l = $xml[$i];
            }
            $newXML .= $l;
        }

        return $newXML;
    }





    /**
     * Gets the inmediate parent of a given nodename.
     *
     * @param string $referenceNodeName
     * @return self
     */
    public function createCleanDocumentForReferenceNode(string $referenceNodeName): self
    {
        // Esto funciona
        $singleNode = $this->getReferencedNode($referenceNodeName);

        return $this->createCleanDocumentForSingleNode($singleNode);
    }

    /**
     * Gets the inmediate parent of a given nodename.
     *
     * @param string $referenceNodeName
     * @return self
     */
    public function createCleanDocumentForSingleNode(DOMElement $singleNode): self
    {
        // Esto funciona
        $parentNode = $singleNode ? $singleNode->parentNode : $this->documentElement;

        $savedXmlDTE = $parentNode->ownerDocument->saveXML($parentNode);

        return self::fromXmlString(
            $savedXmlDTE
            //str_replace('<DTE xmlns="http://www.sii.cl/SiiDte"', '<DTE', $savedXmlDTE),
            //LIBXML_NSCLEAN
        );
    }

    public function toDte(): SiiDte
    {
        $savedC14N = $this->C14N();

        return SiiDte::fromXMLWithNamespace($savedC14N);
    }

    public function getReferenceNodeC14N(?string $referenceNode = null)
    {

        // DOMDocument whose direct children are the signigning subject and its signature
        // Ej DTE, AEC, Cesion, DTECedido
        $EntityDocument = $this->createCleanDocumentForReferenceNode($referenceNode);

        // DOMNode of the sign subject
        $nodeToBeSigned = $EntityDocument->getReferencedNode($referenceNode);

        return $nodeToBeSigned->C14N();
    }

    /**
     * Check document XML schema against arbitrary XSD.
     *
     * @param string $xsd The xsd (relative to storage_path/schemas)
     *
     * @return object ( description_of_the_return_value )
     */
    public function validateWithCustomSchema(
        $xsd = null
    ): object {
        $xsd = $xsd ?? $this->getSchema();
        $schemaCheck = (object) [

            'errors' => null,
            'is_valid' => true,
            'xsd' => null,
        ];
        if ($xsd === null) {
            return $schemaCheck;
        }

        $schemaCheck->xsd = storage_path(\sprintf('schemas/%s', \str_replace(storage_path('schemas'), '', $xsd)));

        libxml_clear_errors();
        $schemaCheck->is_valid = $this->schemaValidate($schemaCheck->xsd);

        if (!$schemaCheck->is_valid) {
            /*
             * @var \App\Exceptions\XmlException[]
             */
            $schemaCheck->errors = $this->getErrors(true);
        }

        return $schemaCheck;
    }

    public function checkDigestUsingSubjectNode(DOMElement $singleNode = null): SiiDocumentValidationResult
    {
        $EntityDocument = $this->createCleanDocumentForSingleNode($singleNode);

        return $EntityDocument->checkDigestUsingReference($singleNode->nodeName);
    }

    /**
     * Gets the public key.
     *
     * @param \|string $modulus  The modulus
     * @param \|string $exponent The exponent
     *
     * @return string the public key
     */
    public function getPublicKeyFromModulusExponent(?DOMElement $Signature = null): ?string
    {
        $Signature = $Signature ?? $this->getSignatureOfDomElement($this->documentElement);
        $modulus = $Signature->getElementsByTagName('Modulus')->item(0)->nodeValue;
        $exponent = $Signature->getElementsByTagName('Exponent')->item(0)->nodeValue;

        $rsa = new \phpseclib\Crypt\RSA();
        $modulus = new \phpseclib\Math\BigInteger(\base64_decode($modulus, true), 256);
        $exponent = new \phpseclib\Math\BigInteger(\base64_decode($exponent, true), 256);
        $rsa->loadKey(['n' => $modulus, 'e' => $exponent]);
        $rsa->setPublicKey();

        return tap($rsa->getPublicKey(), function ($KeyFromModulusExponent) use ($Signature) {
            return;
            $SignatureSignedInfoC15N = Str::of($Signature->C14N())->betweenInclusive('<SignedInfo', 'SignedInfo>')->__toString();

            $SignatureSignedInfoC14N = $Signature->getElementsByTagName('SignedInfo')->item(0)->C14N();

            $SignedInfo = self::fromXmlString($SignatureSignedInfoC14N);
            tap($this->toArray($Signature), function (array $sigObj) {
                $signatureArr = $sigObj['Signature'];
                $SignedInfo = $signatureArr['SignedInfo'];
                $SignatureValue = $signatureArr['SignatureValue'];

                $KeyInfo = (object) Misc::arrayToObject($signatureArr['KeyInfo']);
                kdd(
                    //    $this->generate(objectToArray $SignedInfo)
                    $SignedInfo,
                    $SignatureValue,
                    $KeyInfo
                );
            });

            // kdd($SignatureSignedInfoC14N, $SignatureSignedInfoC15N);

            $C14SignedInfo = $SignedInfo->C14N();
            $SignatureValue = $this->getSignatureValue($Signature);
            dd($SignatureValue);
            if (1 === \openssl_verify($SignatureSignedInfoC14N, \base64_decode($SignatureValue, true), $KeyFromModulusExponent)) {
                kdd('VALIDA CON MODULUS EXPONENT');
            }
        });
    }

    public function getSignatureValue(?DOMElement $Signature = null): ?string
    {
        $Signature = $Signature ?? $this->getSignatureOfDomElement($this->documentElement);

        return $Signature ? $Signature->getElementsByTagName('SignatureValue')->item(0)->nodeValue : null;
    }
}
