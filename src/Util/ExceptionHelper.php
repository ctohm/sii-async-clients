<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Util;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Monolog\Utils;
use Throwable;

\defined('STDIN') || \define('STDIN', \fopen('php://stdin', 'rb'));

\defined('STDOUT') || \define('STDOUT', \fopen('php://stdout', 'wb'));

\defined('STDERR') || \define('STDERR', \fopen('php://stderr', 'wb'));
/**
 * This class describes an exception helper.
 *
 * @method static string aecExpectingMoreCessions(...$args)
 * @method static string authErrorSemilla(...$args)
 * @method static string documentMissingXmlContent(...$args)
 * @method static string requestErrorSoap(...$args)
 */
final class ExceptionHelper
{
    public const CANNOT_PARSE_DTE_DATA = 400;
    public const RUT_NO_ES_CESIONARIO = 401;
    public const AUTH_ERROR_SEMILLA = 402;
    public const AUTH_ERROR_FIRMA_SOLICITUD_TOKEN = 403;
    public const DOCUMENT_IS_DAMAGED_OR_INVALID = 405;
    public const MISMATCHED_SIGNATURE_REFERENCE = 406;
    public const DTE_ERROR_LOADXML = 408;
    public const FIRMA_ERROR = 409;
    public const DOCUMENT_IS_LOCKED = 410;
    public const DOCUMENT_MISSING_XML_CONTENT = 411;
    public const UPLOAD_ERROR_XML = 412;
    public const REQUEST_ERROR_SOAP = 413;
    public const COMPRESS_ERROR_ZIP = 415;
    public const COMPRESS_ERROR_READ = 416;
    public const UPLOAD_ERROR_CURL = 417;
    public const DTE_ERROR_TIPO = 418;
    public const DTE_ERROR_GETDATOS = 422;
    public const DOCUMENT_MISSING_SIGNER = 425;
    public const NODE_NOT_REACHABLE = 426;
    public const DTE_ERROR_FIRMA = 428;
    public const REQUEST_ERROR_BODY = 431;

    public const UPLOAD_ERROR_500 = 500;
    public const SII_ERROR_CERTIFICADO = 501;
    public const UPLOAD_ERROR_FIRMA = 511;
    public const REQUEST_SUCCESSFUL = 0;
    public const AEC_INCONSISTENT_SEQUENCE = 512;
    public const AEC_CONTENT_CANNOT_BE_VERIFIED = 513;
    public const AEC_EXPECTING_MORE_CESSIONS = 514;

    /**
     * @var int
     */
    public static $maxNormalizationDepth = 5;

    /**
     * @var int
     */
    public static $maxNormalizationLength = 1000;

    /**
     * @var int
     */
    public static $maxTraceLength = 24;

    /**
     * @var null|bool
     */
    public static $VAR_DUMPER = null;

    /**
     * { var_description }.
     *
     * @var array<int,string>
     */
    private static $error_messages = [
        self::AEC_CONTENT_CANNOT_BE_VERIFIED => 'El AEC original no se pudo validar. Se interrumpe el procedimiento de recesión',
        self::AEC_INCONSISTENT_SEQUENCE => 'This AEC cannot be sined until it has exactly %d cesiones, but it has only %d',
        self::AUTH_ERROR_FIRMA_SOLICITUD_TOKEN => 'No fue posible firmar getToken',
        self::AUTH_ERROR_SEMILLA => 'No fue posible obtener la semilla',
        self::CANNOT_PARSE_DTE_DATA => 'No fue posible convertir el XML a array para extraer los datos del  %s %s ',
        self::COMPRESS_ERROR_READ => 'No se puede leer el archivo que se desea comprimir',
        self::COMPRESS_ERROR_ZIP => 'No fue posible crear el archivo ZIP',
        self::DOCUMENT_IS_LOCKED => 'The %s "%s" cannot be modified. You cannot sign or alter rehidrated documents',
        self::DOCUMENT_MISSING_SIGNER => "This %s instance doesn't have a Digital Certificate Signer. Pass it to this method or in this class constructor",
        self::DOCUMENT_MISSING_XML_CONTENT => 'The document is missing its %s content. Cannot continue',
        self::DTE_ERROR_FIRMA => 'No se pudo generar la firma del documento %s %s',
        self::DTE_ERROR_GETDATOS => 'No se pudo extraer los datos del DTE ',
        self::DTE_ERROR_LOADXML => 'No fue posible cargar el XML del DTE',
        self::DTE_ERROR_TIPO => 'No existe la definición del tipo de documento para el código %d',
        self::FIRMA_ERROR => '%s',
        self::REQUEST_ERROR_BODY => 'No se obtuvo respuesta para %s en %d intentos',
        self::REQUEST_ERROR_SOAP => 'Error al ejecutar consulta a webservice soap. %s',
        self::REQUEST_SUCCESSFUL => 'Envío ok',
        self::RUT_NO_ES_CESIONARIO => 'Sólo el cesionario vigente (%s) puede receder el documento',
        self::SII_ERROR_CERTIFICADO => 'No se pudo leer el certificado X.509 del SII número %d',
        self::UPLOAD_ERROR_500 => 'Falló el envío automático al SII con error 500',
        self::UPLOAD_ERROR_CURL => 'Falló el envío automático al SII. %s',
        self::UPLOAD_ERROR_FIRMA => 'Error en firma del documento',
        self::UPLOAD_ERROR_XML => 'Error al convertir respuesta de envío automático del SII a XML: %s',
        self::MISMATCHED_SIGNATURE_REFERENCE => 'Se intentó firmar la referencia %d, pero el ID del elemento %s es "%s"',
        self::DOCUMENT_IS_DAMAGED_OR_INVALID => 'documentElement of the subject XML is damaged, invalid or empty',
        self::NODE_NOT_REACHABLE => 'No se pudo acceder al nodo %s ',
        self::AEC_EXPECTING_MORE_CESSIONS => 'This AEC cannot be signed until it has exactly %d cesiones, but it has only %d',
    ];

    /**
     * Handle dynamic static method calls into the model.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        // e.g. method requestErrorSoap($args) becomes
        // static::get(static::REQUEST_ERROR_SOAP,$args)
        $error_key_code = \mb_strtoupper(Str::snake($method));
        $args = Arr::first($args) instanceof \Throwable
            ? [self::getExceptionMessage(Arr::first($args))]
            : Arr::flatten($args);

        if (!\defined(
            \sprintf('%s::%s', self::class, $error_key_code)
        )) {
            return \sprintf('Error code %s thrown with arguments: %s', $error_key_code, \json_encode($args));
        }

        return self::get(\constant(\sprintf('%s::%s', self::class, $error_key_code)), $args);
    }

    /**
     * Organizes responses by grouping errors and warnings smartly.
     */
    public static function classifyErrorsAndWarnings(Collection $results): Collection
    {
        $errors = [];
        $warnings = [];

        $anomaliesClassified = ['errors' => [], 'warnings' => []];
        //kdump([            "errors"=>$results->only(['errors']),            "warnings"=>$results->only(['warnings']),        ]);
        $classifiedArray = $results->only(['errors'])->merge($results->get('warnings'));

        $classifiedArray = $classifiedArray->reduce(static function ($accum, $anomalyGroup) {
            $flatmsg = collect($anomalyGroup)->flatMap(static function ($errmessages, $elemento) {
                return collect($errmessages)->map(static function ($errmessage) use ($elemento) {
                    if ($errmessage instanceof \Throwable) {
                        $errmessage = $errmessage->getMessage();
                    }

                    if (\is_array($errmessage)) {
                        $errmessage = \trim(\implode(' ', $errmessage));
                    }

                    return \sprintf('%s, %s', $elemento, \trim($errmessage));
                });
            });

            return $flatmsg->merge($accum);
        }, $anomaliesClassified);

        $classifiedArray = $classifiedArray->filter()
            ->reduce(static function ($accum, $errmessage) {
                if (\mb_strpos($errmessage, ', XML_') !== false && \mb_strpos($errmessage, ' at line ') !== false) {
                    $errmessage = \preg_replace('/:\d+ at line \d+:/', ' ', $errmessage);
                }

                if ((\mb_strpos($errmessage, 'not expected') !== false
                    || \mb_strpos($errmessage, 'mismatched_digests') !== false)) {
                    $accum['warnings'][] = \str_replace(["Element '{http://www.sii.cl/SiiDte}"], ["'"], $errmessage);
                } else {
                    $accum['errors'][] = $errmessage;
                }

                return $accum;
            }, $anomaliesClassified);

        return $results->except(['errors', 'warnings'])->merge($classifiedArray);
    }

    /**
     * Gets the exception information.
     *
     * @param \Throwable $exception The exception
     * @param int        $depth     The depth
     *
     * @return ((int|string)[]|mixed)[] the exception information
     *
     * @psalm-return list<array{message: string, code?: int|string, file?: string, line?: int}|mixed>
     */
    public static function getExceptionInfo(Throwable $exception, $depth = 4): array
    {
        $exception_dump = [];

        for ($i = 0; $i <= $depth; ++$i) {
            if (!$exception) {
                break;
            }
            $exceptionInfo = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => \str_replace(base_path(), '', $exception->getFile() ?? ''),
                'line' => $exception->getLine(),
            ];

            if (app()->isProduction()) {
                $exception_dump[] = ['message' => $exceptionInfo['message']];
            } else {
                $exception_dump[] = $exceptionInfo;
                $trace = $exception->getTrace();

                for ($traceIndex = 0; $traceIndex <= $depth; ++$traceIndex) {
                    $exception_dump[] = $trace[$traceIndex];
                }
            }
            $exception = $exception->getPrevious();
        }

        return $exception_dump;
    }

    /**
     * Translates and maybe interpolates error constants into meaningful error strings.
     *
     * @param mixed $error_code
     *
     * @return string
     */
    public static function get($error_code, ?array $args = null)
    {
        if (!isset(self::$error_messages[(int) $error_code])) {
            return (string) $error_code;
        }

        if (!\is_array($args)) {
            $args = [
                $args,
            ];
        }

        return \vsprintf(
            self::$error_messages[(int) $error_code],
            $args ?? []
        );
    }

    /**
     * Normalizes given exception with or without its own stack trace based on
     * `includeStacktraces` property.
     *
     * @param Exception|Throwable $e
     *
     * @return ((mixed|string)[]|float|int|string)[]
     *
     * @psalm-return array{thrown_at?: float, class: string, message: string, code: int|string, file: string, trace?: non-empty-list<mixed|string>, previous?: array}
     */
    public static function normalizeException(Throwable $e, int $depth = 1, bool $includeStacktraces = true): array
    {
        $class = Utils::getClass($e);

        // TODO 2.0 only check for Throwable
        if (!$e instanceof Exception && !$e instanceof Throwable) {
            throw new \InvalidArgumentException('Exception/Throwable expected, got ' . \gettype($e) . ' / ' . $class);
        }

        $data = [
            'class' => $class,
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => \str_replace(base_path(), '', $e->getFile()) . ':' . $e->getLine(),
        ];

        if ($includeStacktraces) {
            $trace = $e->getTrace();
            $data['trace'] = self::normalizeBackTrace($trace, $depth, base_path());
        }

        if ($depth >= self::$maxNormalizationDepth) {
            return $data;
        }
        $previous = $e->getPrevious();

        if ($previous && $previous instanceof Throwable) {
            $data['previous'] = self::normalizeException($previous, $depth + 1, true);
        }

        return \array_merge(['thrown_at' => \microtime(true)], $data);
    }

    public static function normalizeBackTrace(array $trace, int $depth = 1, string $base_path = '')
    {
        $traceArray = [];

        foreach ($trace as $index => $frame) {
            if ($index > self::$maxTraceLength) {
                break;
            }

            if (isset($frame['file'])) {
                $traceArray[] = \str_replace($base_path, '', $frame['file']) . ':' . $frame['line'];
            } elseif (isset($frame['function']) && '{closure}' === $frame['function']) {
                // We should again normalize the frames, because it might contain invalid items
                $traceArray[] = $frame['function'];
            } elseif (\is_string($frame)) {
                $traceArray[] = \str_replace($base_path, '', $frame);
            } else {
                // We should again normalize the frames, because it might contain invalid items
                $frame = self::normalize($frame, $depth);
                $traceArray[] = $frame;
            }
        }

        return $traceArray;
    }

    /**
     * Gets a very simplified exception message.
     *
     * @param \Throwable $e { parameter_description }
     *
     * @return string the exception message
     */
    public static function getExceptionMessage(Throwable $e)
    {
        $msg = $e->getMessage();
        $trace0 = $e->getTrace()[0];

        if (isset($trace0['args'][1])
            && \is_string($trace0['args'][1])
        ) {
            $msg .= ': ' . $trace0['args'][1];
        }

        return $msg;
    }

    /**
     * Normalizes given $data.
     *
     * @param mixed $data
     * @param mixed $depth
     *
     * @return mixed
     */
    private static function normalize($data, $depth = 0)
    {
        if ($depth > self::$maxNormalizationDepth) {
            return 'Over 9 levels deep, aborting normalization';
        }

        if (\is_array($data) || $data instanceof \Traversable) {
            $normalized = [];

            $count = 1;

            foreach ($data as $key => $value) {
                if ($count++ > self::$maxNormalizationLength) {
                    $normalized['...'] = \sprintf('Over %d items  , aborting normalization', self::$maxNormalizationLength);

                    break;
                }

                $normalized[$key] = self::normalize($value, $depth + 1);
            }

            return $normalized;
        }

        if ($data instanceof Exception || $data instanceof Throwable) {
            return self::normalizeException($data, $depth);
        }
        //kdump($data);
        return $data;
    }
}
