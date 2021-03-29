<?php

/**
 * DBThor Cesion 1.11.0
 */

namespace CTOhm\SiiAsyncClients\Util;

use DBThor\Sii\SiiDte;
use ForceUTF8\Encoding;
use Illuminate\Support\Str;
use Onnov\DetectEncoding\EncodingDetector;
use Pest\Support\Str as PestStr;
use PhpCsFixer\Fixer\Basic\EncodingFixer;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Throwable;

class EncodingHelper
{
    const TRIM = true;
    const NO_TRIM = false;
    private string $xml_string = '';
    public static $predefined_encodings = ['ASCII', 'UTF-8',  'ISO-8859-1', 'WINDOWS-1252'];

    private function __construct()
    {
    }

    public static function getInstance(string $xml_string): EncodingInstance
    {
        return   EncodingInstance::getInstanceFromString($xml_string);
    }

    public static function getInstanceFromFile(
        string $file_path,
        bool $debug = false,
        bool $addNS = false
    ): EncodingInstance {
        if (!is_file($file_path)) {
            throw new FileException(sprintf('No existe el archivo "%s"', $file_path));
        }
        if (!is_readable($file_path)) {
            throw new FileException(sprintf('No se puede leer el archivo "%s"', $file_path));
        }
        $instance = EncodingInstance::getInstanceFromFile($file_path, $debug, $addNS);

        if ($debug) {
            $instance->dumpEncodingDetections();
        }

        return $instance;
    }

    public static function getInstanceFromString(
        string $string_data,
        bool $debug = false,
        bool $addNs = false
    ): EncodingInstance {
        return  EncodingInstance::getInstanceFromString(
            $string_data,
            $debug,
            $addNs
        );
    }

    public static function isISO(string $string_data)
    {
        return $string_data === utf8_decode(utf8_encode($string_data));
    }

    public static function isUTF8(string $string_data)
    {
        return $string_data === utf8_encode(utf8_decode($string_data));
    }

    public static function checkWithAllEncodings(string $string_data)
    {
        $available_encodings = mb_list_encodings();
        $incompatible_encodings = [];
        foreach ($available_encodings as $encoding) {
            try {
                if (!mb_check_encoding($string_data, $encoding)) {
                    $incompatible_encodings[] = $encoding;
                }
            } catch (Throwable $e) {
                $incompatible_encodings[] = $encoding;
            }
        }

        return $incompatible_encodings;
    }

    public static function runEncodingDetector(string $string_data)
    {
        $detector = new EncodingDetector();

        return $detector->getEncoding($string_data);
    }

    public static function mbDetectEncoding($string_data, ?array $encodings = null)
    {
        if ($encodings === null) {
            $encodings = self::$predefined_encodings;
        }

        return  mb_detect_encoding($string_data, $encodings, true);
    }

    /**
     * Inserts the xmlns declaration to the DTE element, right before version.
     *
     * @param string $xml_string input string
     * @return string $xml_string ensuring SiiDte namespace was declared
     */
    public static function addXmlNsToString(string $xml_string): string
    {
        return  str_replace(
            '<DTE version="1.0">',
            '<DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">',
            $xml_string
        );
    }

    public static function removeXMLDeclaration(string $xml_data): string
    {
        return

            \str_replace(
                [
                    \sprintf('%s%s', '<?xml version="1.0" encoding="ISO-8859-1"?>', \PHP_EOL),
                    \sprintf('%s%s', '<?xml version="1.0"?>', \PHP_EOL),
                    \sprintf('%s', '<?xml version="1.0" encoding="ISO-8859-1"?>'),
                    \sprintf('%s', '<?xml version="1.0"?>'),
                ],
                '',
                self::sortDteNsAndVersion($xml_data)
            );
    }

    public static function sortDteNsAndVersion(string $xml_data): string
    {
        return str_replace(
            ['version="1.0" xmlns="http://www.sii.cl/SiiDte"', '" />'],
            ['xmlns="http://www.sii.cl/SiiDte" version="1.0"', '"/>'],
            $xml_data
        );
    }

    public static function addXMLDeclaration(string $xml_data): string
    {
        return implode(PHP_EOL, ['<?xml version="1.0" encoding="ISO-8859-1"?>', self::removeXMLDeclaration($xml_data)]);
    }

    public static function entityDecode($string, $target_encoding = null)
    {
        return \html_entity_decode($string, \ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_XML1, $target_encoding);
    }

    public static function utf8Char(string $string)
    {
        $ok = preg_match(
            '/^[\xF0-\xF7][\x80-\xBF][\x80-\xBF][\x80-\xBF]
          |^[\xE0-\xEF][\x80-\xBF][\x80-\xBF]
          |^[\xC0-\xDF][\x80-\xBF]
          |^[\x00-\x7f]/sx',
            $string,
            $match
        );

        return $ok ? $match : false;
    }

    public static function removeBOM(string $xml_string)
    {
        return Encoding::removeBOM($xml_string);
    }

    /**
     * Determines if non standard new lines.
     *
     * @param string   $xml_data  The xml data
     * @param bool  $throw     The throw
     *
     * @throws \Exception  (description)
     *
     * @return string  True if non standard new lines, False otherwise.
     */
    public static function standardizeNewLines(string $xml_data, bool $debug = false): string
    {
        $hex = \mb_strtoupper(\bin2hex($xml_data));

        $crlf = (false !== \mb_strpos($xml_data, '0D0A'));
        $cr_normalized_to_lf_hex = \str_replace('0D0A', '0A', $hex);

        $bom = (false !== \mb_strpos($cr_normalized_to_lf_hex, 'EFBBBF'));

        if ($debug || $bom || $crlf) {
            kdump(['BOM' => $bom, 'CRLF' => $crlf, 'encoding' => self::detectEncoding($xml_data)]);
        }
        $withoutBOM = \str_replace('EFBBBF', '', $cr_normalized_to_lf_hex);

        $cr_normalized_to_lf = str_replace(["\r\n", "\n\r", "\r"], PHP_EOL, \hex2bin($withoutBOM));

        return $cr_normalized_to_lf;
    }

    /**
     * UTF Encoding Definition Method.
     *
     * @param string $text
     *
     * @return bool
     */
    public static function isUtf(string $text): bool
    {
        return (bool) preg_match('/./u', $text);
    }

    public static function detectEncoding(string $utf8_or_latin1_or_mixed_string)
    {
        if (\mb_check_encoding($utf8_or_latin1_or_mixed_string, 'ASCII') && self::mbDetectEncoding($utf8_or_latin1_or_mixed_string) === 'ASCII') {
            return 'ASCII';
        }
        if (\mb_check_encoding($utf8_or_latin1_or_mixed_string, 'UTF-8') && self::isUtf($utf8_or_latin1_or_mixed_string)) {
            return  'UTF-8';
        }
        if (\mb_check_encoding($utf8_or_latin1_or_mixed_string, 'ISO-8859-1') && self::mbDetectEncoding($utf8_or_latin1_or_mixed_string) === 'ISO-8859-1') {
            return 'ISO-8859-1';
        }
        if (self::isBinary($utf8_or_latin1_or_mixed_string)) {
            return 'binary';
        }
        //return mb_detect_encoding($utf8_or_latin1_or_mixed_string);
        $detector = new EncodingDetector();
        $copy = strval($utf8_or_latin1_or_mixed_string);

        return $detector->getEncoding($copy);
    }

    public static function isBinary(string $str): bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0;
    }

    /**
     * { function_description }.
     *
     * @param string   $utf8_or_latin1_or_mixed_string  The latin 1 string
     * @param bool  $debug         The debug
     *
     * @return string  ( description_of_the_return_value )
     */
    public static function convertLatin1Tildes(string $utf8_or_latin1_or_mixed_string, bool $debug = false): string
    {
        //$isUtf = self::isUtf($utf8_or_latin1_or_mixed_string);
        //$detected = $detector->getEncoding($safeCopy);
        //$cr_normalized_to_lf = self::standardizeNewLines($utf8_or_latin1_or_mixed_string, $debug);

        $encoding = self::detectEncoding($utf8_or_latin1_or_mixed_string);

        return ($encoding === 'ISO-8859-1') ? \iconv('ISO-8859-1', 'UTF-8', $utf8_or_latin1_or_mixed_string) : Encoding::toUTF8($utf8_or_latin1_or_mixed_string);
        //$utf8String = Encoding::toUTF8($cr_normalized_to_lf, Encoding::ICONV_TRANSLIT);
    }

    /**
     * { function_description }.
     *
     * @param string   $utf8_or_latin1_or_mixed_string  The latin 1 string
     * @param bool  $debug         The debug
     *
     * @return string  ( description_of_the_return_value )
     */
    public static function xconvertLatin1Tildes(string $utf8_or_latin1_or_mixed_string, bool $debug = false): string
    {
        $encoding = self::detectEncoding($utf8_or_latin1_or_mixed_string);
        $converted = ($encoding === 'ISO-8859-1') ? \iconv(
            'ISO-8859-1',
            'UTF-8',
            $utf8_or_latin1_or_mixed_string
        ) : Encoding::toUTF8($utf8_or_latin1_or_mixed_string);

        $utf8String = Encoding::toUTF8($utf8_or_latin1_or_mixed_string, Encoding::ICONV_TRANSLIT);
        if ($debug) {
            kdump($utf8_or_latin1_or_mixed_string, $utf8String, '$converted,$utf8String');
            kdiff($converted, $utf8String, '$converted,$utf8String');
        }

        return $utf8String;
    }

    public static function removeComments(string $xml_data): string
    {
        return  preg_replace('/<\!--.*?-->/', '', $xml_data);
    }

    /**
     * Converts < > and quotes to characters.
     *
     * @param string  $txt  The text
     *
     * @return string  converted string
     */
    public static function entitiesToChars($txt)
    {
        // si no se paso un texto o bien es un número no se hace nada
        if (!is_string($txt)) {
            return $txt;
        }

        // convertir "predefined entities" de XML
        $txt2 = str_replace(
            ['&amp;', '&#38;', '&lt;', '&#60;', '&gt;', '&#62', '&quot;', '&#34;', '&apos;', '&#39;'],
            ['&', '&', '<', '<', '>', '>', '"', '"', '\'', '\''],
            $txt
        );

        // entregar texto sanitizado
        return str_replace('&', '&amp;', $txt2);
    }

    public static function detectFileEncoding($filepath)
    {
        // VALIDATE $filepath !!!
        $output = [];
        exec('file -i ' . $filepath, $output);
        if (isset($output[0])) {
            $ex = explode('charset=', $output[0]);

            return isset($ex[1]) ? strtoupper(str_replace('us-ascii', 'ASCII', $ex[1])) : null;
        }

        return null;
    }

    public static function detectStringEncoding($xml_string)
    {
        $output = [];
        exec(sprintf("echo '%s' | file -i - ", $xml_string), $output);
        if (isset($output[0])) {
            $ex = explode('charset=', $output[0]);

            return isset($ex[1]) ? strtoupper(str_replace('us-ascii', 'ASCII', $ex[1])) : null;
        }

        return null;
    }

    /**
     * Determines if non standard new lines.
     *
     * @param string   $xml_data  The xml data
     * @param bool  $throw     The throw
     *
     * @throws \Exception  (description)
     *
     * @return bool  True if non standard new lines, False otherwise.
     */
    public static function checkForNonStandardNewLines(string $xml_data, bool $throw = false)
    {
        $hex = \mb_strtoupper(\bin2hex($xml_data));
        $crlf = false;

        foreach (["\r", "\r\n", "\n\r"] as $token) {
            if (false !== \mb_strpos($hex, '0D0A') || false !== \mb_strpos($xml_data, $token)) {
                $crlf = true;

                break;
            }
        }

        if ($throw && $crlf) {
            throw new \Exception('Content has non standard new lines (CRLF)', 1);
        }

        return $crlf;
    }

    /**
     * Ensures the output is encoded as ISO-8859-1.
     *
     * @todo Será mejor hacerlo con mb_convert_encoding($utf8EncodedString, 'ISO-8859-1') ?
     *
     * @param string   $utf8EncodedString            a string that's (maybe) UTF8 encoded
     *
     * @return string the same string encoded as ISO-8859-1
     */
    public static function utf2iso(string $utf8EncodedString): string
    {
        $decoded = \utf8_decode($utf8EncodedString);
        $str = $decoded;
        if (self::isUtf($utf8EncodedString)) {
            $str = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $utf8EncodedString);
            kdiff($decoded, $str, 'utf8 vs iconv');
        }

        return 'ISO-8859-1' !== \mb_detect_encoding($utf8EncodedString, ['UTF-8', 'ISO-8859-1'], true) ? $decoded : $utf8EncodedString;
    }

    public static function diagnoseChars(string $replaceBomAndCR)
    {
        $latin1_length = \mb_strlen($replaceBomAndCR);
        $res = [];
        for ($i = 0; $latin1_length - 1 > $i; $i++) {
            $char = \mb_substr($replaceBomAndCR, $i, 1);
            $hexchar = \mb_strtoupper(\bin2hex($char));

            $nextchar = \mb_substr($replaceBomAndCR, $i + 1, 1);
            $nexthexchar = \mb_strtoupper(\bin2hex($nextchar));
            $ord = \mb_ord($char);
            if ($ord > 127 || !$ord || mb_strlen($hexchar) > 2) {
                $res[] = [
                    'pos' => $i,
                    'chr' => $char,
                    'isBinary' => self::isBinary($char),
                    'ord' => $ord,
                    'hexchar' => $hexchar,
                    'charlen' => mb_strlen($char),
                    'context' => trim(\mb_substr($replaceBomAndCR, $i - 10, 20)),
                ];
            }
        }
        kdump($res);
    }

    public static function toUTF8($isoEncodedString, bool $debug = false): string
    {
        $xml_string = self::convertLatin1Tildes($isoEncodedString, $debug);

        return self::isUtf($xml_string) ? $xml_string : Encoding::fixUTF8($xml_string);
    }

    /*public static function toUTF8($isoEncodedString)
    {
        if (self::isUtf($isoEncodedString)) {
            return $isoEncodedString;
        }
        $detectedEncoding = self::mbDetectEncoding($isoEncodedString);
        try {
            $utf8EncodedString = iconv($detectedEncoding, "UTF-8//TRANSLIT", $isoEncodedString);
        } catch (Throwable $e) {
            kdump([$detectedEncoding => $e->getMessage()]);


            $utf8EncodedString = Encoding::fixUTF8($isoEncodedString, Encoding::WITHOUT_ICONV);
        }

        //        return $isValidUTF8 ? $isoEncodedString : \iconv('ISO-8859-1', 'UTF-8', $isoEncodedString);
        return  $utf8EncodedString;
    }*/

    /**
     * Ensures the output is encoded as UTF8.
     *
     * @param string $isoEncodedString a string that's (maybe) ISO-8859-1 encoded
     * @param bool   $enforce          if true then actually convert
     * @ignore Currently is a noop unless $enforce is set to true
     *
     * @return string the same string encoded as UTF8
     */
    public static function iso2utf8($isoEncodedString, $enforce = false)
    {
        //return $enforce ? self::toUTF8($isoEncodedString) : $isoEncodedString;
        $isIsoEncoded = self::mbDetectEncoding($isoEncodedString) === 'ISO-8859-1';

        return $isIsoEncoded && $enforce ? \utf8_encode($isoEncodedString) : $isoEncodedString;
    }
}
