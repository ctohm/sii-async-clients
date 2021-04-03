<?php

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Helpers;

use DOMDocument;
use Illuminate\Support\Str;

/*
 * @see https://www.php.net/manual/en/function.libxml-get-errors.php
 */

\libxml_use_internal_errors(true);
/**
 * @/extends DOMDocument
 */
class SiiDOMDocument extends \DOMDocument
{
    const VERSION = '1.0';
    const ENCODING = 'ISO-8859-1';
    const NO_FORMAT_OUTPUT = false;
    const FORMAT_OUTPUT = true;

    const PRESERVE_WHITE_SPACE = true;
    const NO_PRESERVE_WHITE_SPACE = false;

    /**
     * @var bool
     */
    public $formatOutput;

    /**
     * Constructs a new instance.
     *
     * @param string $version      the version number of the document as part of the XML declaration
     * @param string $encoding     Internal encoding for the XML parsing handler
     * @param bool   $formatOutput The format output
     */
    public function __construct(
        string $version = self::VERSION,
        string $encoding = self::ENCODING,
        bool $formatOutput = self::NO_FORMAT_OUTPUT,
        bool $preserveWhiteSpace = self::PRESERVE_WHITE_SPACE
    ) {
        parent::__construct($version, $encoding);
        $this->formatOutput = $formatOutput;
        $this->preserveWhiteSpace = $preserveWhiteSpace;
    }

    /**
     * Carga un string XML en el Objeto desde un string.
     *
     * @see https://www.php.net/manual/en/DOMDocument.loadxml.php
     *
     * @param string   $source  The source
     * @param null|int $options
     *
     * @return bool|\DOMDocument ( description_of_the_return_value )
     */
    public function loadXML(
        $source,
        $options = null
    ) {
        if (!$source) {
            return false;
        }
        $xml = parent::loadXML(Str::of($source)->isoToUTF8(), $options);

        foreach ($this->childNodes as $child) {
            if (\XML_DOCUMENT_TYPE_NODE === $child->nodeType) {
                $this->removeChild($child);

                break;
            }
        }

        return $xml;
    }

    /**
     * entrega el nombre del tag raíz del XML.
     *
     * @return string
     */
    public function getName()
    {
        return $this->documentElement->tagName;
    }

    /**
     * Wrapper for saveXML(), fixing entities as per SII requirements.
     *
     * @param null|\DOMNode $node    The node
     * @param null|int      $options The options (Sólo LIBXML_NOEMPTYTAG está soportado)
     */
    public function saveXML(?\DOMNode $node = null, $options = null): string
    {
        $original_xml = parent::saveXML($node, $options);

        return self::normalizeQuotes($original_xml);
    }

    /**
     * Wrapper for C14N() fixing entities as per SII requirements.
     *
     * @param null|bool               $exclusive     Retain only the nodes matched by the xpath or ns prefixes
     * @param null|bool               $with_comments Retains comments
     * @param array<array-key, mixed> $xpath         The xpath to filter by
     * @param null|mixed              $ns_prefixes   The ns prefixes to filter by
     *
     * @return string
     * @psalm-supress MethodSignatureMismatch
     */
    public function C14N(
        $exclusive = null,
        $with_comments = null,
        ?array $xpath = null,
        $ns_prefixes = null
    ) {
        $original_xml = parent::C14N($exclusive, $with_comments, $xpath, $ns_prefixes);

        if (!\mb_check_encoding($original_xml, 'utf-8')) {
            throw new \Exception('not utf8 valid!');
        }

        return self::normalizeQuotes($original_xml);
    }

    /**
     * @return ((((mixed|string)[]|mixed|string)[]|false|mixed|string)[]|false|string)[]|false|string
     *
     * @psalm-return array<string, array<int|string, array<int|string, array<int|string, mixed|string>|mixed|string>|false|mixed|string>|false|string>|false|string
     */
    public function toArraySkipTagnames(?\DOMElement $dom = null, array $skip_tagnames = [])
    {
        $xmlArray = [];

        // Caso raíz. No se declaró el DOMElement
        if (!$dom) {
            // Si esta instancia no tiene un documento cargado, tampoco hay array
            if (!$this->documentElement || !$this->documentElement->tagName) {
                return false;
            }
            // Recursión: el array debe tener una única llave de entrada que es el tag raíz del documento
            return [
                $this->documentElement->tagName => $this->toArraySkipTagnames($this->documentElement, $skip_tagnames),
            ];
        }
        // agregar atributos del nodo
        if ($dom->hasAttributes() && !\in_array('@attributes', $skip_tagnames, true)) {
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
                $child instanceof \DOMText
                // Y tiene contenido
                && $textContent = \trim($child->textContent)
            ) {
                if (\in_array('@value', $skip_tagnames, true)) {
                    continue;
                }

                if ( // Si el $dom tiene un solo nodo texto
                    1 === $dom->childNodes->length
                    // Y el array está vacío (o sea no hay atributes)
                    && empty($xmlArray)
                ) {
                    // El resultado es el valor del texto es el valor del texto
                    return $textContent;
                }

                // En otro caso para rehidratar se requiere meter el texto en una propiedad @value
                $xmlArray['@value'] = $textContent;
            } elseif ($child instanceof \DOMElement && $childTagName = $child->tagName
                // &&
            ) {
                if (\in_array($child->tagName, $skip_tagnames, true)) {
                    //  dump([$childTagName]);
                    continue;
                }
                // Si el array no tiene un nodo con el tagName del hijo, lo declaramos como array
                $xmlArray[$childTagName] = $xmlArray[$childTagName] ?? [];

                // El nodo hijo es un DOMElement. Su valor sale de la recursión sobre el presente método
                $xmlArray[$childTagName][] = $this->toArraySkipTagnames($child, $skip_tagnames);
            }
        }
        // No sé cómo podríamos llegar aquí pero por si acaso
        if (!\is_array($xmlArray)) {
            return $xmlArray;
        }

        foreach ($xmlArray as $childTagName => $childValue) {
            $childTags[] = $childTagName;

            // Si declaramos un hijo como array, pero no tiene siblings, se asigna su valor directamente
            if (\is_array($childValue)) {
                $debugPayload[$childTagName]['count'] = \count($childValue);

                if (\count($childValue) === 1) {
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

    /**
     * Alternative to "toArray" using a recursion instead of pass by reference.
     *
     * @param null|\DOMElement $dom The dom
     *
     * @return (((((((array|mixed)[]|mixed|string)[]|mixed|null|string)[]|false|mixed|null|string)[]|false|mixed|null|string)[]|false|null|string)[]|false|string)[]|false|string
     *
     * @psalm-return array<string, array<int|string, array<array-key, array<array-key, array<array-key, array<array-key, array<array-key, array|mixed>|mixed|string>|mixed|null|string>|false|mixed|null|string>|false|mixed|null|string>|false|null|string>|false|string>|false|string
     */
    public function toArray(?\DOMElement $dom = null)
    {
        $xmlArray = [];

        // Caso raíz. No se declaró el DOMElement
        if (!$dom) {
            // Si esta instancia no tiene un documento cargado, tampoco hay array
            if (!$this->documentElement || !$this->documentElement->tagName) {
                return false;
            }
            // Recursión: el array debe tener una única llave de entrada que es el tag raíz del documento
            return [
                $this->documentElement->tagName => $this->toArray($this->documentElement),
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
                $child instanceof \DOMText
                // Y tiene contenido
                && $textContent = \trim($child->textContent)
            ) {
                if ( // Si el $dom tiene un solo nodo texto
                    1 === $dom->childNodes->length
                    // Y el array está vacío (o sea no hay atributes)
                    && empty($xmlArray)
                ) {
                    // El resultado es el valor del texto es el valor del texto
                    return $textContent;
                }
                // En otro caso para rehidratar se requiere meter el texto en una propiedad @value
                $xmlArray['@value'] = $textContent;
            } elseif ($child instanceof \DOMElement && $childTagName = $child->tagName) {
                // Si el array no tiene un nodo con el tagName del hijo, lo declaramos como array
                $xmlArray[$childTagName] = $xmlArray[$childTagName] ?? [];

                // El nodo hijo es un DOMElement. Su valor sale de la recursión sobre el presente método
                $xmlArray[$childTagName][] = $this->toArray($child);
            }
        }
        // No sé cómo podríamos llegar aquí pero por si acaso
        if (!\is_array($xmlArray)) {
            return $xmlArray;
        }

        foreach ($xmlArray as $childTagName => $childValue) {
            $childTags[] = $childTagName;

            // Si declaramos un hijo como array, pero no tiene siblings, se asigna su valor directamente
            if (\is_array($childValue)) {
                $debugPayload[$childTagName]['count'] = \count($childValue);

                if (\count($childValue) === 1) {
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

    /**
     * Fixes HTML entities ' (&apos;) y " (&quot;) as per SII requirements.
     *
     * @param string $xml The xml
     */
    public static function normalizeQuotes(string $xml): string
    {
        $newXML = '';
        $n_letras = \mb_strlen($xml);

        $isTagOpen = false;

        for ($i = 0; $i < $n_letras; ++$i) {
            if ($isTagOpen && '>' === $xml[$i]) {
                $isTagOpen = false;
            }

            if (!$isTagOpen && '<' === $xml[$i]) {
                $isTagOpen = true;
            }

            if (!$isTagOpen) {
                $l = '\'' === $xml[$i] ? '&apos;' : ('"' === $xml[$i] ? '&quot;' : $xml[$i]);
            } else {
                $l = $xml[$i];
            }
            $newXML .= $l;
        }

        return $newXML;
    }
}
