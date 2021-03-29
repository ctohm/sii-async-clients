<?php

/**
 * DBThor Cesion 1.11.0
 */

namespace DBThor\Helpers;

use App\Exceptions\XmlException;
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
 * @method static string requestErrorSoap(...$args)
 * @method static string documentMissingXmlContent(...$args)
 * @method static string aecExpectingMoreCessions(...$args)
 */
class ExceptionHelper
{
    const CANNOT_PARSE_DTE_DATA = 400;
    const RUT_NO_ES_CESIONARIO = 401;
    const AUTH_ERROR_SEMILLA = 402;
    const AUTH_ERROR_FIRMA_SOLICITUD_TOKEN = 403;
    const DOCUMENT_IS_DAMAGED_OR_INVALID = 405;
    const MISMATCHED_SIGNATURE_REFERENCE = 406;
    const DTE_ERROR_LOADXML = 408;
    const FIRMA_ERROR = 409;
    const DOCUMENT_IS_LOCKED = 410;
    const DOCUMENT_MISSING_XML_CONTENT = 411;
    const UPLOAD_ERROR_XML = 412;
    const REQUEST_ERROR_SOAP = 413;
    const COMPRESS_ERROR_ZIP = 415;
    const COMPRESS_ERROR_READ = 416;
    const UPLOAD_ERROR_CURL = 417;
    const DTE_ERROR_TIPO = 418;
    const DTE_ERROR_GETDATOS = 422;
    const DOCUMENT_MISSING_SIGNER = 425;
    const NODE_NOT_REACHABLE = 426;
    const DTE_ERROR_FIRMA = 428;
    const REQUEST_ERROR_BODY = 431;

    const UPLOAD_ERROR_500 = 500;
    const SII_ERROR_CERTIFICADO = 501;
    const UPLOAD_ERROR_FIRMA = 511;
    const REQUEST_SUCCESSFUL = 0;
    const AEC_INCONSISTENT_SEQUENCE = 512;
    const AEC_CONTENT_CANNOT_BE_VERIFIED = 513;
    const AEC_EXPECTING_MORE_CESSIONS = 514;

    /**
     * { var_description }.
     *
     * @var array<int,string>
     */
    private static $error_messages = [

        self::AEC_CONTENT_CANNOT_BE_VERIFIED   => 'El AEC original no se pudo validar. Se interrumpe el procedimiento de recesión',
        self::AEC_INCONSISTENT_SEQUENCE        => 'This AEC cannot be sined until it has exactly %d cesiones, but it has only %d',
        self::AUTH_ERROR_FIRMA_SOLICITUD_TOKEN => 'No fue posible firmar getToken',
        self::AUTH_ERROR_SEMILLA               => 'No fue posible obtener la semilla',
        self::CANNOT_PARSE_DTE_DATA            => 'No fue posible convertir el XML a array para extraer los datos del  %s %s ',
        self::COMPRESS_ERROR_READ              => 'No se puede leer el archivo que se desea comprimir',
        self::COMPRESS_ERROR_ZIP               => 'No fue posible crear el archivo ZIP',
        self::DOCUMENT_IS_LOCKED               => 'The %s "%s" cannot be modified. You cannot sign or alter rehidrated documents',
        self::DOCUMENT_MISSING_SIGNER          => "This %s instance doesn't have a Digital Certificate Signer. Pass it to this method or in this class constructor",
        self::DOCUMENT_MISSING_XML_CONTENT     => 'The document is missing its %s content. Cannot continue',
        self::DTE_ERROR_FIRMA                  => 'No se pudo generar la firma del documento %s %s',
        self::DTE_ERROR_GETDATOS               => 'No se pudo extraer los datos del DTE ',
        self::DTE_ERROR_LOADXML                => 'No fue posible cargar el XML del DTE',
        self::DTE_ERROR_TIPO                   => 'No existe la definición del tipo de documento para el código %d',
        self::FIRMA_ERROR                      => '%s',
        self::REQUEST_ERROR_BODY               => 'No se obtuvo respuesta para %s en %d intentos',
        self::REQUEST_ERROR_SOAP               => 'Error al ejecutar consulta a webservice soap. %s',
        self::REQUEST_SUCCESSFUL               => 'Envío ok',
        self::RUT_NO_ES_CESIONARIO             => 'Sólo el cesionario vigente (%s) puede receder el documento',
        self::SII_ERROR_CERTIFICADO            => 'No se pudo leer el certificado X.509 del SII número %d',
        self::UPLOAD_ERROR_500                 => 'Falló el envío automático al SII con error 500',
        self::UPLOAD_ERROR_CURL                => 'Falló el envío automático al SII. %s',
        self::UPLOAD_ERROR_FIRMA               => 'Error en firma del documento',
        self::UPLOAD_ERROR_XML                 => 'Error al convertir respuesta de envío automático del SII a XML: %s',
        self::MISMATCHED_SIGNATURE_REFERENCE =>  'Se intentó firmar la referencia %d, pero el ID del elemento %s es "%s"',
        self::DOCUMENT_IS_DAMAGED_OR_INVALID => 'documentElement of the subject XML is damaged, invalid or empty',
        self::NODE_NOT_REACHABLE => 'No se pudo acceder al nodo %s ',
        self::AEC_EXPECTING_MORE_CESSIONS => 'This AEC cannot be signed until it has exactly %d cesiones, but it has only %d',
    ];

    /**
     * Translates and maybe interpolates error constants into meaningful error strings.
     *
     * @param int   $error_code
     * @param array ...$args    The arguments
     *
     * @return string
     */
    public static function get($error_code, array $args = null)
    {
        if (!isset(self::$error_messages[(int) $error_code])) {
            return  strval($error_code);
        }
        if (!is_array($args)) {
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
     * Handle dynamic static method calls into the model.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        // e.g. method requestErrorSoap($args) becomes
        // static::get(static::REQUEST_ERROR_SOAP,$args)
        $error_key_code = strtoupper(Str::snake($method));
        $args = Arr::first($args) instanceof \Throwable
            ? [static::getExceptionMessage(Arr::first($args))]
            : Arr::flatten($args);
        kdump(['error_key_code' => $error_key_code, 'args' => $args]);
        if (!defined(
            sprintf('%s::%s', static::class, $error_key_code)
        )) {
            return sprintf('Error code %s thrown with arguments: %s', $error_key_code, json_encode($args));
        }

        return static::get(constant(sprintf('%s::%s', static::class, $error_key_code)), $args);
    }

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
    public static $maxTraceLength = 16;

    /**
     * @var bool|null
     */
    public static $VAR_DUMPER = null;

    /**
     * Organizes responses by grouping errors and warnings smartly.
     *
     * @param \Illuminate\Support\Collection $results
     * @return  \Illuminate\Support\Collection
     */
    public static function classifyErrorsAndWarnings(Collection $results): \Illuminate\Support\Collection
    {
        $errors = [];
        $warnings = [];

        $anomaliesClassified = ['errors' => [], 'warnings' => []];
        //kdump([            "errors"=>$results->only(['errors']),            "warnings"=>$results->only(['warnings']),        ]);
        $classifiedArray = $results->only(['errors'])->merge($results->get('warnings'));

        $classifiedArray = $classifiedArray->reduce(function ($accum, $anomalyGroup) {
            $flatmsg = collect($anomalyGroup)->flatMap(function ($errmessages, $elemento) {
                return collect($errmessages)->map(function ($errmessage) use ($elemento) {
                    if ($errmessage instanceof \Throwable) {
                        $errmessage = $errmessage->getMessage();
                    }
                    if (is_array($errmessage)) {
                        $errmessage = trim(implode(' ', $errmessage));
                    }

                    return sprintf('%s, %s', $elemento, trim($errmessage));
                });
            });

            return $flatmsg->merge($accum);
        }, $anomaliesClassified);

        $classifiedArray = collect($classifiedArray)->filter()
            ->reduce(function ($accum, $errmessage) {
                if (strpos($errmessage, ', XML_') !== false && strpos($errmessage, ' at line ') !== false) {
                    $errmessage = preg_replace('/:\d+ at line \d+:/', ' ', $errmessage);
                }
                if ((strpos($errmessage, 'not expected') !== false
                    || strpos($errmessage, 'mismatched_digests') !== false)) {
                    $accum['warnings'][] = str_replace(["Element '{http://www.sii.cl/SiiDte}"], ["'"], $errmessage);
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
     * @param \Throwable  $exception  The exception
     * @param int     $depth      The depth
     *
     * @return array  The exception information.
     */
    public static function getExceptionInfo(\Throwable $exception, $depth = 4)
    {
        $exception_dump = [];

        for ($i = 0; $i <= $depth; $i++) {
            if (!$exception) {
                break;
            }
            $exceptionInfo = [
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
                'file'    => str_replace(base_path(), '', $exception->getFile() ?? ''),
                'line'    => $exception->getLine(),
            ];

            debuglog()->debug($exceptionInfo);
            if (app()->environment('production')) {
                $exception_dump[] = ['message' => $exceptionInfo['message']];
            } else {
                $exception_dump[] = $exceptionInfo;
                $trace = $exception->getTrace();
                for ($traceIndex = 0; $traceIndex <= $depth; $traceIndex++) {
                    $exception_dump[] = $trace[$traceIndex];
                }
            }
            $exception = $exception->getPrevious();
        }

        return $exception_dump;
    }

    /**
     * Log error description when submitting AEC to the RPETC.
     *
     * @param int  $statusCode         The status code
     * @param string   $idAec              The identifier aec
     * @param array   $submissionDetails  The submission details
     */
    public static function warnRtcSubmissionError(int $statusCode, $idAec, $submissionDetails): void
    {
        /*
         * 0 Envío recibido OK.
         * 1 Rut usuario autenticado no tiene permiso para enviar en empresa Cedente.
         * 2 Error en tamaño del archivo enviado.
         * 4 Faltan parámetros de entrada.
         * 5 Error de autenticación, TOKEN inválido, no existe o está expirado.
         * 6 Empresa no es DTE.
         * 9 Error Interno.
         * 10 Error Interno
         */
        $error = [
            1  => 'Rut usuario autenticado no tiene permiso para enviar en empresa Cedente',
            2  => 'Error en tamaño del archivo enviado',
            4  => 'Faltan parámetros de entrada',
            5  => 'Error de autenticación, TOKEN inválido, no existe o está expirado',
            6  => 'Empresa no es DTE',
            9  => 'Error Interno',
            10 => 'Error Interno',
        ];
        debuglog()->warning(
            sprintf(
                'Submission of AEC %s failed with error: %s',
                $idAec,
                $error[$statusCode]
            ),
            $submissionDetails
        );
    }

    /**
     * Normalizes given exception with or without its own stack trace based on
     * `includeStacktraces` property.
     *
     * @param Exception|Throwable $e
     * @param int                 $depth
     * @param bool                $includeStacktraces
     *
     * @return array
     */
    public static function normalizeException(Throwable $e, int $depth = 1, bool $includeStacktraces = true): array
    {
        $class = Utils::getClass($e);

        // TODO 2.0 only check for Throwable
        if (!$e instanceof Exception && !$e instanceof Throwable) {
            throw new \InvalidArgumentException('Exception/Throwable expected, got ' . \gettype($e) . ' / ' . $class);
        }

        $data = [
            'class'   => $class,
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'file'    => \str_replace(/*base_path()*/'', '', $e->getFile()) . ':' . $e->getLine(),
        ];
        $maxTraceLength = ($e instanceof XMLException || 'App\Exceptions\XmlException' === $data['class']) ? 1 : self::$maxTraceLength;

        if ($e instanceof XMLException || \mb_strpos($class, 'XMLException')) {
            $data['message'] = str_replace(["\n", PHP_EOL], ' ', $data['message']);

            return $data;
        }
        if ($includeStacktraces) {
            $trace = $e->getTrace();

            foreach ($trace as $index => $frame) {
                if ($index > $maxTraceLength) {
                    break;
                }

                if (isset($frame['file'])) {
                    //$data['trace'][] = \str_replace(base_path(), '', $frame['file']).':'.$frame['line'];
                } elseif (isset($frame['function']) && '{closure}' === $frame['function']) {
                    // We should again normalize the frames, because it might contain invalid items
                    $data['trace'][] = $frame['function'];
                } elseif (\is_string($frame)) {
                    //  $data['trace'][] = \str_replace(base_path(), '', $frame);
                } else {
                    // We should again normalize the frames, because it might contain invalid items
                    $frame = self::normalize(
                        $frame,
                        $depth
                    );
                    $data['trace'][] = $frame;
                }
            }
        }

        if ($depth >= self::$maxNormalizationDepth) {
            return $data;
        }
        $previous = $e->getPrevious();

        if ($previous && $previous instanceof Throwable) {
            $data['previous'] = self::normalizeException($previous, $depth + 1, !($e instanceof XMLException));
        }
        $data = array_merge(['thrown_at' => microtime(true)], $data);

        return $data;
    }

    /**
     * Gets a very simplified exception message.
     *
     * @param \Throwable  $e  { parameter_description }
     *
     * @return string  The exception message.
     */
    public static function getExceptionMessage(\Throwable $e)
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
    protected static function normalize($data, $depth = 0)
    {
        if ($depth > self::$maxNormalizationDepth) {
            return 'Over 9 levels deep, aborting normalization';
        }

        if (\is_array($data) || $data instanceof \Traversable) {
            $normalized = [];

            $count = 1;

            foreach ($data as $key => $value) {
                if ($count++ > self::$maxNormalizationLength) {
                    $normalized['...'] = \sprintf('Over %d items (%d total), aborting normalization', self::$maxNormalizationLength, \count($data));

                    break;
                }

                $normalized[$key] = self::normalize(
                    $value,
                    $depth + 1
                );
            }

            return $normalized;
        }

        if ($data instanceof XMLException) {
            return self::normalizeException(
                $data,
                2,
                false
            );
        }

        if ($data instanceof Exception || $data instanceof Throwable) {
            return self::normalizeException(
                $data,
                $depth
            );
        }
        //kdump($data);
        return $data;
    }
}
