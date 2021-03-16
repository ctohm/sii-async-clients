<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients;

use Carbon\Carbon;
use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use CTOhm\SiiAsyncClients\Util\ExceptionHelper;
use CTOhm\SiiAsyncClients\Util\Misc;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class RpetcClient extends RestClient
{
    /**
     * @var int
     */
    public const TIPO_DEUDOR = 0;
    /**
     * @var int
     */
    public const TIPO_CEDENTE = 1;
    /**
     * @var int
     */
    public const TIPO_CESIONARIO = 2;
    public const SERVICIO = 'wsRPETCConsulta';

    /**
     * @var array
     */
    public static $tipoDiccionario = [
        'TIPO_DEUDOR', 'TIPO_CEDENTE', 'TIPO_CESIONARIO',
    ];

    public static string $common_uri = 'https://palena.sii.cl/cgi_rtc/RTC/';

    /**
     * @var null|string
     */
    private $representandoA;

    public function __construct(SiiSignatureInterface $siiSignature, array $clientOptions = [])
    {
        self::$common_uri = $clientOptions['baseURL'] ?? self::$common_uri;
        self::$CommonOptions['delay'] = config('sii-clients.default_request_delay_ms'); // milliseconds
        parent::__construct($siiSignature, $clientOptions);
    }

    /**
     * Gets the client.
     *
     * @return \GuzzleHttp\Client the client
     */
    public function getClient(array $clientOptions = []): \GuzzleHttp\Client
    {
        $multiHandler = app(CurlMultiHandler::class);

        $handlerStack = HandlerStack::create($multiHandler);

        $handlerStack->push(self::debugRequestHeaders());
        $mergedOpts = \array_merge([
            'handler' => $handlerStack,
            'debug' => false,
            'base_uri' => static::BASE_URL,
            'curl.options' => [
                \CURLOPT_SSLVERSION => \CURL_SSLVERSION_TLSv1_2,
            ],
        ], $clientOptions ?? []);

        self::$client = new \GuzzleHttp\Client($mergedOpts);

        return self::$client;
    }

    /**
     * Undocumented function.
     */
    public function clearRepresentacion(): void
    {
        $this->representandoA = null;
        $this->clear();
    }

    /**
     * Downloads an aec.
     *
     * @return null|string ( description_of_the_return_value )
     */
    public function getAecFile(string $rut_empresa, Structures\EventosHistoricosParameters $requestPayload)
    {
        $representacion = $this->representar($rut_empresa);

        if ($representacion instanceof \Exception) {
            dump($representacion);

            return null;
        }

        if ($representacion !== $rut_empresa) {
            dump($representacion);
            dump('NO SE PUDO REPRESENTAR A ' . $rut_empresa);

            return null;
        }

        try {
            $dte_url = 'RTCDescargarXmlCons.cgi';
            $response = $this->sendSiiRequest(
                'POST',
                $this->getUrl($dte_url, self::$common_uri),
                [
                    'headers' => ['referer' => 'https://palena.sii.cl/rtc/RTC/RTCConsultas.html'],

                    'form_params' => [
                        'rut_emisor' => $requestPayload->rutEmisor,
                        'dv_emisor' => $requestPayload->dvEmisor,
                        'tipo_docto' => $requestPayload->tipoDoc,
                        'folio' => $requestPayload->folio,
                        'clave' => '',
                        'botonxml' => 'xml',
                    ],
                ]
                //true
            );

            //$xml      = simplexml_load_string($response->contents);
            $aec = $response->getBody()->getContents();

            $this->clearRepresentacion();

            return $aec;
        } catch (\Exception $e) {
            $this->clearRepresentacion();

            return null;
            //self::dumpHistory();
        }
    }

    /**
     * Undocumented function.
     *
     * @param \Carbon\Carbon $fecha_desde
     * @param \Carbon\Carbon $fecha_hasta
     */
    public function getCesionesRecibidas(string $rut_empresa, ?Carbon $fecha_desde = null, ?Carbon $fecha_hasta = null): ?Collection
    {
        $dte_url = 'RTCConsultaCesiones.cgi';
        $referer = 'RTCConsultaCesionesHtml.cgi';

        $CESIONES = '';

        $representacion = $this->representar($rut_empresa);

        if ($representacion instanceof \Exception) {
            kdump($representacion);

            return null;
        }

        if ($representacion !== $rut_empresa) {
            kdump($representacion);
            kdump('NO SE PUDO REPRESENTAR A ' . $rut_empresa);

            return null;
        }

        $query = [
            'TXTXML' => 'TXT',
            'DESDE' => $fecha_desde->format('dmY'),
            'HASTA' => $fecha_hasta->format('dmY'),
        ];

        try {
            $query['TIPOCONSULTA'] = self::TIPO_CESIONARIO;

            $response = $this->sendSiiRequest(
                'POST',
                $this->getUrl($dte_url, self::$common_uri),
                [
                    'headers' => ['referer' => $this->getUrl($referer, self::$common_uri)],
                    //'debug'       => true,
                    'form_params' => $query,
                ]
                //true
            );
            $contents = $response->getBody()->getContents();

            $cleancontents = \explode("\n", \trim($contents));
            \array_shift($cleancontents);
            //array_pop($cleancontents);
            $fields = \explode(';', \array_shift($cleancontents));

            $mapped = collect($cleancontents)->map(static function ($str_row) {
                return \explode(';', $str_row);
            })->filter(static function ($row) use ($fields) {
                return \count($row) === \count($fields);
            })->map(static function ($row) use ($fields) {
                return \array_combine(
                    \array_map(static fn ($key) => Str::snake(\mb_strtolower($key)), $fields),
                    $row
                );
            });

            //array_combine(array_map(fn($key)=>Str::snake(strtolower($key)),array_keys($cesion)),array_values($cesion))

            return $mapped;
            // dump($cesiones);
            // self::dumpHistory();
        } catch (\Exception $e) {
            dump($e);

            return null;
            //self::dumpHistory();
        }

        return $CESIONES;
    }

    /**
     * Undocumented function.
     *
     * @return \Exception|string
     */
    private function representar(string $rut_empresa)
    {
        if (self::$authenticatedOnSii && $this->representandoA === $rut_empresa) {
            return $this->representandoA;
        }
        // If already authenticated, this method is a no-op
        $this->authOnSii();
        [$rutEmpresa, $dvEmpresa] = \explode('-', $rut_empresa);

        try {
            $certpaths = self::getCertFiles();
            //dump(['self::$certpaths path: ' => $certpaths]);

            $response = $this->sendSiiRequest('POST', 'https://herculesr.sii.cl/cgi_AUT2000/admRepresentar.cgi', [
                'headers' => [
                    'Origin' => 'https://herculesr.sii.cl',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer' => 'https://herculesr.sii.cl/cgi_AUT2000/admRPDOBuild.cgi',
                ],
                'form_params' => ['RUT_RPDO' => $rutEmpresa, 'APPLS' => 'RPETC'],
                'cert' => $certpaths['client'],
                'ssl_key' => $certpaths['key'],
                'verify' => $certpaths['ca'] ?? null,
            ]);

            $representarDOM = $response->getBody()->getContents();

            $crawler = new Crawler($representarDOM);
            $realizada = $crawler->filter('#my-wrapper h2')->eq(0)->text();
            $representandoA = $crawler->filter('#my-wrapper .bloque p ')->eq(0)->text();
            $representandoRUT = $crawler->filter('#my-wrapper .bloque p ')->eq(1)->text();

            if ($representandoRUT) {
                $parsedRUT = \str_replace(['Rut:', 'document.write(formateaMiles(', '"."', ')', ',', ';', ' '], '', $representandoRUT);

                $representandoA = $parsedRUT;
            }

            if ($representandoA !== $rut_empresa) {
                kdump(['response_from_sii' => $representandoA]);

                throw new \Exception('NO SE PUDO REPRESENTAR A ' . $rut_empresa);
            }
            $this->representandoA = $representandoA;
            //  kdump('Representando a ' . $this->representandoA);

            return $representandoA;
        } catch (\Exception $e) {
            kdump($e);

            return $e;
        }
    }
    /**
     * Downloads an aec.
     *
     * @param string  $rut_empresa   The rut empresa
     * @param string  $id_documento  The identifier documento
     *
     * @return array
     */
    public function getDetalleCesionRTC(string $rut_empresa, string $id_documento)
    {
        if (!$representacion = $this->representar($rut_empresa)) {
            return null;
        }

        [
            $rutEmisorConDV, $tipo_doc, $folio
        ] = \explode('_', $id_documento);

        try {
            [
                $rut_emisor, $dv_emisor
            ] = Misc::validaRut($rutEmisorConDV);

            $dte_url = 'RTCConsulta.cgi';
            $response = $this->sendSiiRequest(
                'POST',
                self::getUrl($dte_url, self::$common_uri),
                [
                    'headers' => ['referer' => 'https://palena.sii.cl/rtc/RTC/RTCConsultas.html'],
                    //'debug'       => true,
                    'form_params' => [
                        'rut_emisor' => $rut_emisor,
                        'dv_emisor' => $dv_emisor,
                        'tipo_docto' => $tipo_doc,
                        'folio' => $folio,
                        'clave' => '',
                        'botonxml' => 'xml',
                    ],
                ]
                //true
            );

            $detalleConsulta = $response->getBody()->getContents();

            [
                $tablaContenidoHTML, $infoDetalle
            ] = $this->parseDetalleHtml($detalleConsulta);

            $this->clearRepresentacion();

            return $infoDetalle ?? [];
        } catch (\Exception $e) {
            $this->clearRepresentacion();


            return ['error' => $e->getMessage()];
            //self::dumpHistory();
        }
    }
    /**
     * @return (mixed|null|string)[]
     *
     * @psalm-return array{0: string, 1: mixed|null}
     */
    private static function parseDetalleHtml(
        string $detalleConsultaDOM
    ): array {
        //dump(['parseTablaDatos' => $index]);
        $crawler = new Crawler($detalleConsultaDOM);

        try {
            $tablaContenido = $crawler->filter('body > table')->eq(1);
            $content = [];

            if (!$tablaContenido) {
                return [
                    $detalleConsultaDOM, null,
                ];
            }
            $tablaContenidoHTML = $tablaContenido->html();

            $rows = $tablaContenido->filter('table > tr');

            if (!$rows->count()) {
                return [
                    $detalleConsultaDOM, null,
                ];
            }

            $content = $rows->each(static function ($node) {
                $tds = $node->filter('td');

                if (3 > $tds->count()) {
                    return;
                }

                return [
                    'title' => $tds->eq(0)->text(),
                    'content' => $tds->eq(2)->text(),
                ];
            });

            $items = collect($content)->filter(static function ($item) {
                return 8 < \mb_strlen($item['content'] ?? ':');
            });

            $infoDetalle = $items->reduce(static function ($accum, $item) {
                $accum[Str::slug($item['title'], '_')] = $item['content'];

                return $accum;
            }, []);

            return [\sprintf('<table>%s %s %s </table>', \PHP_EOL, $tablaContenidoHTML, \PHP_EOL), $infoDetalle];
        } catch (\InvalidArgumentException $e) {
            dump($e);
        }

        return [
            $detalleConsultaDOM, null,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    private function getCesionesFromCSV(Crawler $tableHtml, string $rut_empresa)
    {
        $tableHtml = $tableHtml->filter('tbody > tr')->reduce(static function ($tr) use ($rut_empresa) {
            $tds = $tr->filter('td');

            return $tds->count() >= 9 && $tds->eq(2)->text() === $rut_empresa;
        });

        $cesiones = collect($tableHtml->each(static function ($tr) {
            $tds = $tr->filter('td');
            $tipoDocText = $tds->eq(7)->text();
            $tipoDoc = 'FACTURA ELECTRONICA' === $tipoDocText ? 33 : 34;
            $rutEmisor = $tds->eq(6)->text();

            $folio = $tds->eq(8)->text();
            $dte_id = \sprintf('%s_%d_%d', $rutEmisor, $tipoDoc, $folio);

            return [
                'dte_id' => $dte_id,
                'rutCedente' => $tds->eq(1)->text(),
                'rutCesionario' => $tds->eq(2)->text(),
                'fechaCesion' => $tds->eq(4)->text(),
                'fechaEmision' => $tds->eq(9)->text(),
                'rutDeudor' => $tds->eq(3)->text(),
                'tipoDocText' => $tipoDocText,
                'rutEmisor' => $rutEmisor,
                'tipoDoc' => $tipoDoc,
                'folio' => $folio,
                'montoCesion' => $tds->eq(5)->text(),
                'montoTotal' => $tds->eq(10)->text(),
            ];
        }));

        return $cesiones;
    }
}
