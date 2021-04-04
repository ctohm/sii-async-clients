<?php

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\AbstractCrawlers;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Psr\Http\Message\RequestInterface;
use function kdump;

abstract class SiiAbstractCrawler
{
    public static $tipos_doc_reverso = [
        'Factura Electronica' => 33,
        'Factura Exenta Electronica' => 34,
        'Liquidacion Factura Electronica' => 43,
        'Factura de Compra Electronica' => 46,
        'Nota de Debito Electronica' => 56,
        'Nota de Credito Electronica' => 61,
        'Guia de Despacho Electronica' => 52,
        'Factura de Exportacion Electronica' => 110,
        'Nota de Debito de Exportacion Electronica' => 111,
        'Nota de Credito de Exportacion Electronica' => 112,
    ];

    public static $diccionario_tipos_dte = [
        33 => 'Factura Electronica',
        34 => 'Factura Exenta Electronica',
        43 => 'Liquidacion Factura Electronica',
        46 => 'Factura de Compra Electronica',
        56 => 'Nota de Debito Electronica',
        61 => 'Nota de Credito Electronica',
        52 => 'Guia de Despacho Electronica',
        110 => 'Factura de Exportacion Electronica',
        111 => 'Nota de Debito de Exportacion Electronica',
        112 => 'Nota de Credito de Exportacion Electronica',
    ];

    /**
     * Gets the or create dump folder.
     *
     * @param string $rut_empresa The rut empresa
     *
     * @return string the or create dump folder
     */
    public static function getOrCreateDumpFolder($rut_empresa)
    {
        $dump_folder = storage_path(\sprintf(
            '%s/%s',
            class_basename(static::class),
            $rut_empresa
        ));

        if (!File::isDirectory($dump_folder)) {
            $created = File::makeDirectory(
                $dump_folder,
                0777,
                true,
                true
            );
        }

        return $dump_folder;
    }

    /**
     * @return \Closure
     *
     * @psalm-return \Closure(callable):\Closure(RequestInterface, array=):mixed
     */
    protected static function debugRequestHeaders()
    {
        return static function (callable $handler) {
            return static function (
                RequestInterface $request,
                array $options = []
            ) use ($handler) {
                $headers = [];
                $debug = $options['debug'] ?? false;

                if (!$debug) {
                    return $handler(
                        $request,
                        $options
                    );
                }

                foreach ($request->getHeaders() as $headername => $headervalue) {
                    $headers[$headername] = $headervalue[0];
                }
                kdump([
                    'headers' => Arr::except($headers, [
                        //'Cookie',
                    ]),
                    'options' => Arr::except($options, [
                        'cookies',
                        'base_uri', 'allow_redirects', 'handler',
                    ]),
                    'method' => $request->getMethod(),
                    'target' => $request->getUri()->__toString(),
                    'body' => $request->getBody()->__toString(),
                ]);

                return $handler(
                    $request,
                    $options
                );
            };
        };
    }

    /**
     * Normaliza el tratamiento de una excepción de Guzzle.
     *
     * @param object                          $res          The response which errored
     * @param ClientException|ServerException $e            Excepción gatillada por guggle
     * @param string                          $route_method URL y método del llamado que falló
     * @param array<array-key, mixed>         $reqParams    The request parameters
     *
     * @return object original response enriched with exception data133
     */
    protected static function dealWithGuzzleException($res, $e, string $route_method, array $reqParams)
    {
        if (!$apiresponse = $e->getResponse()) {
            return $res;
        }

        $res->statusCode = $apiresponse->getStatusCode();
        $res->contents = \sprintf('Ha ocurrido un error con la solicitud %s : %s ', $route_method, $apiresponse->getReasonPhrase());
        $res->trace = $e->getTraceAsString();
        $reqParams['response'] = $res->contents;
        kdump($reqParams);

        return $res;
    }
}
