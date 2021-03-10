<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Util;

use Exception;
use LibXMLError;

final class XmlException extends \RuntimeException
{
    public const XML_ERR_NONE = 0;
    public const XML_ERR_WARNING = 1; // : A simple warning
    public const XML_ERR_ERROR = 2; // : A recoverable error
    public const XML_ERR_FATAL = 3; // : A fatal error
    public const XML_ERR_TAG_NAME_MISMATCH = 76; // : 76
    public const XML_ERR_TAG_NOT_FINISHED = 77; // : 77
    public const XML_ERR_STANDALONE_VALUE = 78; // : 78
    public const XML_ERR_ENCODING_NAME = 79; // : 79
    public const XML_ERR_HYPHEN_IN_COMMENT = 80; // : 80
    public const XML_ERR_INVALID_ENCODING = 81; // : 81
    public const XML_ERR_EXT_ENTITY_STANDALONE = 82; // : 82
    public const XML_ERR_CONDSEC_INVALID = 83; // : 83
    public const XML_ERR_VALUE_REQUIRED = 84; // : 84
    public const XML_ERR_NOT_WELL_BALANCED = 85; // : 85
    public const XML_ERR_EXTRA_CONTENT = 86; // : 86

    /**
     * Entity Errors.
     *
     * @var int
     */
    public const XML_ERR_ENTITY_CHAR_ERROR = 87; // : 87
    public const XML_ERR_ENTITY_PE_INTERNAL = 88; // : 88
    public const XML_ERR_ENTITY_LOOP = 89; // : 89
    public const XML_ERR_ENTITY_BOUNDARY = 90; // : 90

    public const XML_ERR_INVALID_URI = 91; // : 91
    public const XML_ERR_URI_FRAGMENT = 92; // : 92
    public const XML_WAR_CATALOG_PI = 93; // : 93
    public const XML_ERR_NO_DTD = 94; // : 94
    public const XML_ERR_CONDSEC_INVALID_KEYWORD = 95; // : 95
    public const XML_ERR_VERSION_MISSING = 96; // : 96
    public const XML_WAR_UNKNOWN_VERSION = 97; // : 97
    public const XML_WAR_LANG_VALUE = 98; // : 98
    public const XML_WAR_NS_URI = 99; // : 99
    public const XML_WAR_NS_URI_RELATIVE = 100; // : 100
    public const XML_ERR_MISSING_ENCODING = 101; // : 101
    public const XML_WAR_SPACE_VALUE = 102; // : 102
    public const XML_ERR_NOT_STANDALONE = 103; // : 103
    public const XML_ERR_ENTITY_PROCESSING = 104; // : 104
    public const XML_ERR_NOTATION_PROCESSING = 105; // : 105
    public const XML_WAR_NS_COLUMN = 106; // : 106
    public const XML_WAR_ENTITY_REDEFINED = 107; // : 107
    public const XML_ERR_UNKNOWN_VERSION = 108; // : 108
    public const XML_ERR_VERSION_MISMATCH = 109; // : 109
    public const XML_ERR_NAME_TOO_LONG = 110; // : 110
    public const XML_ERR_USER_STOP = 111; // : 111

    /**
     * NS Errors.
     *
     * @var int
     */
    public const XML_NS_ERR_XML_NAMESPACE = 200;
    public const XML_NS_ERR_UNDEFINED_NAMESPACE = 201; // : 201
    public const XML_NS_ERR_QNAME = 202; // : 202
    public const XML_NS_ERR_ATTRIBUTE_REDEFINED = 203; // : 203
    public const XML_NS_ERR_COLON = 205; // : 204

    /**
     * DTD Errors.
     *
     * @var int
     */
    public const XML_DTD_ATTRIBUTE_DEFAULT = 500;
    public const XML_DTD_DUP_TOKEN = 541;

    /**
     * RNGP Errors.
     *
     * @var int
     */
    public const XML_RNGP_ANYNAME_ATTR_ANCESTOR = 1000;
    public const XML_RNGP_XML_NS = 1122;

    /**
     * XPATH Errors.
     *
     * @var int
     */
    public const XML_XPATH_EXPRESSION_OK = 1200;
    public const XML_XPATH_INVALID_CHAR_ERROR = 1221;

    /**
     * @var int
     */
    public const XML_IO_UNKNOWN = 1500;
    public const XML_IO_EAFNOSUPPORT = 1556;

    public const XML_XINCLUDE_RECURSION = 1600;
    public const XML_XINCLUDE_FRAGMENT_ID = 1618;

    public const XML_CATALOG_MISSING_ATTR = 1650;
    public const XML_CATALOG_RECURSION = 1654;

    /**
     * SCHEMA Errors.
     *
     * @var int
     */
    public const XML_SCHEMAP_PREFIX_UNDEFINED = 1700;
    public const XML_SCHEMAV_DOCUMENT_ELEMENT_MISSING = 1872;
    public const XML_SCHEMAV_MISC = 1879;
    public const XML_SCHEMAP_SRC_SIMPLE_TYPE_1 = 3000;
    public const XML_SCHEMAP_COS_ALL_LIMITED = 3091;

    /**
     * C14N Errors.
     *
     * @var int
     */
    public const XML_C14N_CREATE_CTXT = 1950;
    public const XML_C14N_REQUIRES_UTF8 = 1951; // : 1951
    public const XML_C14N_CREATE_STACK = 1952; // : 1952
    public const XML_C14N_INVALID_NODE = 1953; // : 1953
    public const XML_C14N_UNKNOW_NODE = 1954; // : 1954
    public const XML_C14N_RELATIVE_NAMESPACE = 1955; // : 1955

    /**
     * CHECK errors.
     *
     * @var int
     */
    public const XML_CHECK_FOUND_ELEMENT = 5000;
    public const XML_CHECK_NAME_NOT_NULL = 5037;

    /**
     * @var string
     */
    protected $message = 'Unknown Error XmlException'; // exception message

    /**
     * @var int
     */
    protected $code = 0; // user defined exception code

    /**
     * @var string
     */
    private $errorMessage = '';

    /**
     * Internal XML Error.
     *
     * @var LibXMLError
     */
    private $originalError;

    public function __construct(LibXMLError $error)
    {
        $this->originalError = $error;

        parent::__construct($this->__toString(), $error->code/*, $this->getSeverity()*/);
    }

    public function __toString()
    {
        $error = $this->getError();

        return \sprintf(
            '%s at line %d:%s %s  ',
            $this->getErrorFamily($error->code),
            $error->line,
            \PHP_EOL,
            \str_replace(',', \PHP_EOL, \trim($error->message)),
        );
    }

    public function __get(
        $key
    ) {
        $error = $this->getError();

        switch ($key) {
            case 'level':
                return $error->level;

                break;
            case 'message':
                return $error->message;

                break;
            case 'code':
                return $error->code;

                break;
            case 'line':
                return $error->line;

                break;
        }
    }

    public static function __set_state($exportedErr) // As of PHP 5.1.0
    {
        $exportedErr['originalError'] = null;

        return $exportedErr;
    }

    /**
     * Determines whether the specified code is ns error.
     *
     * @param int $code The code
     *
     * @return bool true if the specified code is ns error, False otherwise
     */
    public static function isNSError($code)
    {
        return self::XML_NS_ERR_XML_NAMESPACE <= $code && self::XML_NS_ERR_COLON >= $code;
    }

    /**
     * Determines whether the specified code is dtd error.
     *
     * @param int $code The code
     *
     * @return bool true if the specified code is dtd error, False otherwise
     */
    public static function isDTDError($code)
    {
        return self::XML_DTD_ATTRIBUTE_DEFAULT <= $code && self::XML_DTD_DUP_TOKEN >= $code;
    }

    /**
     * Determines whether the specified code is rngp error.
     *
     * @param int $code The code
     *
     * @return bool true if the specified code is rngp error, False otherwise
     */
    public static function isRngpError($code)
    {
        return self::XML_RNGP_ANYNAME_ATTR_ANCESTOR <= $code && self::XML_RNGP_XML_NS >= $code;
    }

    /**
     * Determines whether the specified code is xpath error.
     *
     * @param int $code The code
     *
     * @return bool true if the specified code is xpath error, False otherwise
     */
    public static function isXpathError($code)
    {
        return self::XML_XPATH_EXPRESSION_OK <= $code && self::XML_XPATH_INVALID_CHAR_ERROR >= $code;
    }

    /**
     * Determines whether the specified code is i/o error.
     *
     * @param int $code The code
     *
     * @return bool true if the specified code is i/o error, False otherwise
     */
    public static function isIoError($code)
    {
        return self::XML_IO_UNKNOWN <= $code && self::XML_IO_EAFNOSUPPORT >= $code;
    }

    /**
     * Determines whether the specified code is xinclude error.
     *
     * @param int $code The code
     *
     * @return bool true if the specified code is xinclude error, False otherwise
     */
    public static function isXincludeError($code)
    {
        return self::XML_XINCLUDE_RECURSION <= $code && self::XML_XINCLUDE_FRAGMENT_ID >= $code;
    }

    /**
     * Determines whether the specified code is calog error.
     *
     * @param int $code The code
     *
     * @return bool true if the specified code is calog error, False otherwise
     */
    public static function isCalogError($code)
    {
        return self::XML_CATALOG_MISSING_ATTR <= $code && self::XML_CATALOG_RECURSION >= $code;
    }

    /**
     * Determines whether the specified code is schema error.
     *
     * @param int $code The code
     *
     * @return bool true if the specified code is schema error, False otherwise
     */
    public static function isSchemaError($code)
    {
        return (self::XML_SCHEMAP_PREFIX_UNDEFINED <= $code && self::XML_SCHEMAV_MISC >= $code) || (self::XML_SCHEMAP_SRC_SIMPLE_TYPE_1 <= $code && self::XML_SCHEMAP_COS_ALL_LIMITED >= $code);
    }

    /**
     * @param int $code
     */
    public static function isC14Error($code): bool
    {
        return self::XML_C14N_CREATE_CTXT <= $code && self::XML_C14N_RELATIVE_NAMESPACE >= $code;
    }

    /**
     * Gets the error family.
     *
     * @param int $errCode The error code
     *
     * @return string the error family
     */
    public function getErrorFamily(int $errCode)
    {
        $severityText = $this->getSeverityText();

        if (self::isNSError((int) $errCode)) {
            return \sprintf('XML_NS_%s:%d', $severityText, $errCode);
        }

        if (self::isDTDError((int) $errCode)) {
            return \sprintf('XML_DTD_%s:%d', $severityText, $errCode);
        }

        if (self::isRngpError((int) $errCode)) {
            return \sprintf('XML_RNGP_%s:%d', $severityText, $errCode);
        }

        if (self::isXpathError((int) $errCode)) {
            return \sprintf('XML_XPATH_%s:%d', $severityText, $errCode);
        }

        if (self::isIoError((int) $errCode)) {
            return \sprintf('XML_IO_%s:%d', $severityText, $errCode);
        }

        if (self::isXincludeError((int) $errCode)) {
            return \sprintf('XML_XINCLUDE_%s:%d', $severityText, $errCode);
        }

        if (self::isCalogError((int) $errCode)) {
            return \sprintf('XML_CALOG_%s:%d', $severityText, $errCode);
        }

        if (self::isSchemaError((int) $errCode)) {
            return \sprintf('XML_SCHEMA_%s:%d', $severityText, $errCode);
        }

        if (self::isC14Error((int) $errCode)) {
            return \sprintf('XML_C14_%s:%d', $severityText, $errCode);
        }

        return \sprintf('OTHER_XML_%s:%d', $severityText, $errCode);
    }

    /**
     * Returns an array representation of the object.
     *
     * @return (int|string)[] array representation of the object
     *
     * @psalm-return array{message: string, level: int, code: int, line: int}
     */
    public function toArray(): array
    {
        $error = $this->getError();

        return [
            'message' => $error->message,
            'level' => $this->getSeverity(),
            'code' => $error->code,
            'line' => $error->line,
        ];
    }

    public function getError(): LibXMLError
    {
        return $this->originalError;
    }

    /**
     * Gets the severity.
     *
     * @return int the severity
     *
     * @psalm-return 256|512|4096
     */
    public function getSeverity(): int
    {
        switch ($this->getError()->level) {
            case \LIBXML_ERR_WARNING:
                return \E_USER_WARNING;

                break;
            case \LIBXML_ERR_ERROR:
                return \E_USER_ERROR;

                break;
            case \LIBXML_ERR_FATAL:
                return \E_RECOVERABLE_ERROR;

                break;

            default:
                return \E_USER_WARNING;
        }
    }

    public function getSeverityText(): string
    {
        switch ($this->getError()->level) {
            case \LIBXML_ERR_WARNING:
                return 'WARNING';

                break;
            case \LIBXML_ERR_ERROR:
                return 'ERROR';

                break;
            case \LIBXML_ERR_FATAL:
                return 'FATAL';

                break;

            default:
                return 'NOTICE';
        }
    }
}
