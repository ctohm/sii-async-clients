<?php

/**
 * DBThor Cesion 1.11.0
 */

namespace CTOhm\SiiAsyncClients\Util;

use Closure;
use DBThor\Sii\SiiDte;
use ForceUTF8\Encoding;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Support\Traits\Tappable;
use Onnov\DetectEncoding\EncodingDetector;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Throwable;

class EncodingInstance
{
    use Tappable;
    const TRIM = true;
    const NO_TRIM = false;
    private ?Stringable $xml_string = null;
    private string $original_encoding = '';

    public function __construct($xml_string, ?string $original_encoding = null)
    {
        $this->xml_string = Str::of($xml_string);
        $this->original_encoding = $original_encoding ?? EncodingHelper::detectStringEncoding($xml_string);
    }

    public static function getInstanceFromString(
        string $xml_string,
        bool $debug = false,
        bool $addNS = false
    ): self {
        if ($addNS) {
            $xml_string = EncodingHelper::addXmlNsToString($xml_string);
        }

        return new  static($xml_string, EncodingHelper::detectStringEncoding($xml_string));
    }

    public function hasDeclaration(): bool
    {
        return $this->xml_string->before(PHP_EOL)->contains('<?xml');
    }

    public function getDeclaredEncoding(): ?string
    {
        if (!$this->hasDeclaration()) {
            return null;
        }

        return ($this->xml_string->before(PHP_EOL)->contains('ISO-8859-1')) ? 'ISO-8859-1' : 'UTF-8';
    }

    public static function getInstanceFromFile(
        string $file_path,
        bool $debug = false,
        bool $addNS = false
    ): self {
        $xml_string = with(file_get_contents($file_path), fn ($xml_string) => $addNS ? EncodingHelper::addXmlNsToString($xml_string) : $xml_string);


        return  new static($xml_string, EncodingHelper::detectFileEncoding($file_path));
    }

    public function removeBOM()
    {
        return new static(EncodingHelper::removeBOM($this->xml_string->__toString()), $this->original_encoding);
    }

    public function detectStringEncoding()
    {
        return EncodingHelper::detectStringEncoding($this->xml_string->__toString());
    }

    public function getOriginalEncoding()
    {
        return $this->original_encoding;
    }



    public function mbDetectEncoding(?array $encodings = null)
    {
        return  EncodingHelper::mbDetectEncoding($this->xml_string->__toString(), $encodings, $this->original_encoding);
    }

    public function runEncodingDetector()
    {
        return EncodingHelper::runEncodingDetector($this->xml_string->__toString(), $this->original_encoding);
    }

    public function dumpEncodingDetections(): self
    {
        kdump([

            //'dete->getEncoding' => $this->runEncodingDetector(),
            'detectFileEncoding' =>  $this->getOriginalEncoding(),
            'detectEncoding' => $this->detectEncoding(),
            'mbDetectEncoding' => $this->mbDetectEncoding(),
        ]);

        return $this;
    }

    /**
     * Inserts the xmlns declaration to the DTE element, right before version.
     *
     * @param string $xml_string input string
     * @return string $xml_string ensuring SiiDte namespace was declared
     */
    public function addXmlNsToString(): string
    {
        return new static(EncodingHelper::addXmlNsToString($this->xml_string->__toString()), $this->original_encoding);
    }

    public function removeXMLDeclaration(): string
    {
        return new static(EncodingHelper::removeXMLDeclaration($this->xml_string->__toString()), $this->original_encoding);
    }

    public function withXMLDeclaration(): self
    {
        return new static(EncodingHelper::addXMLDeclaration($this->xml_string->__toString()), $this->original_encoding);
    }

    public function decodeEntities(?string $forced_encoding = null): self
    {
        $isoOrUtf = $this->isIso() ? 'ISO-8859-1' : ($this->isUtf() ? 'UTF-8' : null);
        $target_encoding = $forced_encoding ?? $isoOrUtf;

        return new static(EncodingHelper::entityDecode($this->xml_string->__toString(), $target_encoding), $this->original_encoding);
    }

    public function withoutXMLDeclaration(): self
    {
        return new static(EncodingHelper::removeXMLDeclaration($this->xml_string->__toString()), $this->original_encoding);
    }

    public function toISO(): self
    {
        try {
            //$isoString = Encoding::toISO8859($this->xml_string, Encoding::ICONV_TRANSLIT);
            $isoString = iconv($this->runEncodingDetector(), 'ISO-8859-1//TRANSLIT', $this->xml_string);
        } catch (Throwable $e) {
            kdd($e->getMessage());
            $isoString = Encoding::toISO8859($this->xml_string, Encoding::WITHOUT_ICONV);
        }

        return new static(
            $isoString
        );
    }

    public function toUTF8Iconv(): self
    {
        return new static(iconv($this->runEncodingDetector(), 'UTF-8//TRANSLIT', $this->xml_string));
    }

    public function toUTF8(): self
    {
        return new static(EncodingHelper::toUTF8($this->xml_string->__toString() /*, Encoding::ICONV_TRANSLIT*/), $this->original_encoding);
    }

    public function withoutComments(): self
    {
        return new static(EncodingHelper::removeComments($this->xml_string->__toString()), $this->original_encoding);
    }

    public function toString(bool $trim = false)
    {
        $xml_string = $this->__toString();

        return $trim ? trim($xml_string) : $xml_string;
    }

    public function __toString()
    {
        return $this->xml_string->__toString();
    }

    public function isIso()
    {
        return Str::isAscii($this->xml_string) || $this->mbDetectEncoding() === 'ISO-8859-1';
    }

    public function withStandardNewLines(bool $debug = false): self
    {
        return new static(EncodingHelper::standardizeNewLines($this->xml_string->__toString(), $debug), $this->original_encoding);
    }

    public function detectEncoding()
    {
        return EncodingHelper::detectEncoding($this->xml_string->__toString(), $this->original_encoding);
    }

    public function removeComments(): self
    {
        return new static(EncodingHelper::removeComments($this->xml_string->__toString()), $this->original_encoding);
    }

    public function isUtf()
    {
        return EncodingHelper::isUtf($this->xml_string->__toString(), $this->original_encoding);
    }

    public function diagnoseChars()
    {
        EncodingHelper::diagnoseChars($this->xml_string->__toString(), $this->original_encoding);
    }
}
