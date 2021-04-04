<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients;

use Carbon\Carbon;
use CTOhm\SiiAsyncClients\RequestClients\Structures\CertificatesObjectInterface;
use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use CTOhm\SiiAsyncClients\Util\ExceptionHelper;
use CTOhm\SiiAsyncClients\Util\Misc;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;
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

    protected CertificatesObjectInterface $certificatesObject;

    protected static CertificatesObjectInterface $certs;

    /**
     * @var null|string
     */
    private $representandoA;

    public function __construct(SiiSignatureInterface $siiSignature, array $clientOptions = [])
    {
        self::$common_uri = $clientOptions['baseURL'] ?? self::$common_uri;
        self::$CommonOptions['delay'] = config('sii-clients.default_request_delay_ms'); // milliseconds
        $this->certificatesObject = $siiSignature->getCerts();
        parent::__construct($siiSignature, $clientOptions);
        static::$certs = $this->certificatesObject;
    }

    /**
     * Gets the cert files.
     *
     * @return array{cert:string,ssl_key:string,verify:string|null} array of paths to the cert files
     */
    public static function getCertFiles(): array
    {
        return self::$certs->getPaths();
    }

    /**
     * Gets the cert files.
     *
     * @return array{cert:string,ssl_key:string,verify:string|null} array of paths to the cert files
     */
    public function getCertPaths(): array
    {
        return $this->certificatesObject->getPaths();

        if (\count($this->certpaths) === 0) {
            $this->certFile = \tmpfile();
            $this->pkeyFile = \tmpfile();

            \fwrite($this->pkeyFile, $this->certificatesObject->pkey);
            \fwrite($this->certFile, $this->certificatesObject->cert);

            if ($this->certificatesObject->extracerts) {
                $this->caFile = \tmpfile();

                foreach ($this->certificatesObject->extracerts as $extracert) {
                    \fwrite($this->caFile, $extracert);
                }
            }

            $this->certpaths = [
                'cert' => \stream_get_meta_data($this->certFile)['uri'],
                'ssl_key' => \stream_get_meta_data($this->pkeyFile)['uri'],
                'verify' => $this->caFile ? \stream_get_meta_data($this->caFile)['uri'] : config('sii-clients.cacert_pemfile'),
            ];
        }

        return $this->certpaths;
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
     */
    public function getCesionesRecibidas(
        string $rut_empresa,
        ?Carbon $fecha_desde = null,
        ?Carbon $fecha_hasta = null,
        array $options = ['TXTXML' => 'TXT', 'TIPOCONSULTA' => self::TIPO_CESIONARIO]
    ): ?Collection {
        $representacion = $this->representar($rut_empresa);

        if ($representacion !== $rut_empresa) {
            kdump($representacion);
            kdump('NO SE PUDO REPRESENTAR A ' . $rut_empresa);

            return collect([]);
        }
        $dte_url = 'RTCConsultaCesiones.cgi';
        $referer = 'RTCConsultaCesionesHtml.cgi';

        $CESIONES = '';

        $query = \array_merge([
            'TXTXML' => 'TXT',
            'TIPOCONSULTA' => self::TIPO_CESIONARIO,
        ], $options, [
            'DESDE' => $fecha_desde->format('dmY'),
            'HASTA' => $fecha_hasta->format('dmY'),
        ]);

        try {
            $response = $this->sendSiiRequest(
                'POST',
                $this->getUrl($dte_url, self::$common_uri),
                [
                    'headers' => ['referer' => $this->getUrl($referer, self::$common_uri)],
                    'form_params' => $query, // 'debug' => true
                ]
                //true
            );
            $contents = $response->getBody()->getContents();

            if ('TXT' === $query['TXTXML']) {
                return $this->mapCsvToArray($contents);
            }

            $xml = \simplexml_load_string($contents);
            $xml->registerXPathNamespace('SII', 'http://www.sii.cl/XMLSchema');
            $cesiones = $xml->xpath('.//SII:RESP_BODY/CESION');
            $datos_consulta = $xml->xpath('.//SII:RESP_BODY/DATOS_CONSULTA');
            $consultaCollection = collect(\array_change_key_case(\json_decode(\json_encode($datos_consulta[0]), true)));

            $cesionesCollection = collect($cesiones)->map(static fn (SimpleXMLElement $xmlElement) => \array_change_key_case(\json_decode(\json_encode($xmlElement), true)));

            return $consultaCollection->put('cesiones', $cesionesCollection);
        } catch (\Exception $e) {
            dump($e);

            return null;
            //self::dumpHistory();
        }

        return $CESIONES;
    }

    /**
     * Downloads an aec.
     *
     * @param string $rut_empresa  The rut empresa
     * @param string $id_documento The identifier documento
     *
     * @return null|array
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
     * Undocumented function.
     */
    public function getCertificadosForm(
        string $rut_empresa,
        ?Carbon $fecha_desde = null,
        ?Carbon $fecha_hasta = null,
        array $options = ['TIPOCONSULTA' => self::TIPO_CESIONARIO]
    ): ?Collection {
        $representacion = $this->representar($rut_empresa);

        if ($representacion !== $rut_empresa) {
            kdump('NO SE PUDO REPRESENTAR A ' . $rut_empresa);

            return collect([]);
        }
        $dte_url = 'RTCCertMas.cgi';
        $referer = 'RTCCertMas.cgi';

        $CESIONES = '';

        $query = \array_merge([
            'RUT1' => '',
            'RUT2' => '',

            'TIPOCONSULTA' => self::TIPO_CESIONARIO,
        ], $options, [
            'RUTQ' => $rut_empresa,
            'STEP' => 1,
            'DESDE' => $fecha_desde->format('dmY'),
            'HASTA' => $fecha_hasta->format('dmY'),
        ]);

        try {
            $response = $this->sendSiiRequest(
                'POST',
                $this->getUrl($dte_url, self::$common_uri),
                [
                    'headers' => ['referer' => $this->getUrl($referer, self::$common_uri)],
                    'form_params' => $query,
                ]
                //true
            );
            $contents = $response->getBody()->getContents();
            $cesionesArray = $this->getCesionesFromHTML(new Crawler($contents), $rut_empresa);

            return $cesionesArray;
            $ids = $cesionesArray->map(static fn (array $c) => $c['sii_id']);
            $query2 = [
                'RUT1' => '**',
                'RUT2' => '**',

                'TIPOCONSULTA' => $query['TIPOCONSULTA'],

                'TIPOCERT' => 3,

                'STEP' => 2,
                'DESDE' => $fecha_desde->format('dmY'),
                'HASTA' => $fecha_hasta->format('dmY'),

                'chk' => $ids->join('&chk='),
            ];
            $response = $this->sendSiiRequest(
                'POST',
                $this->getUrl($dte_url, self::$common_uri),
                [
                    'headers' => [
                        'content-type' => 'application/x-www-form-urlencoded',
                        'referer' => $this->getUrl($referer, self::$common_uri),
                    ],
                    'body' => \urldecode(\http_build_query($query2)),
                    //  'debug' => true,
                    'sink' => storage_path('certificados.html'),
                ]
                //true
            );
            $contents = $response->getBody()->getContents();
            $iconverted = \iconv(self::detectStringEncoding($contents), 'UTF-8', $contents);
            Storage::drive('testing')->put('iconverted.html', $iconverted);
            $encoded = \utf8_encode($contents);
            Storage::drive('testing')->put('encoded.html', $encoded);
            $crawler = new Crawler($iconverted);

            $tablas = $crawler->filter('#contenedor > div > table')->each(static function (Crawler $table) {
                //$images = $table                    ->images();
                return $table->outerHtml();
            });
            // kdd($tablas);
        } catch (\Exception $e) {
            dump($e);

            return null;
            //self::dumpHistory();
        }

        return $CESIONES;
    }

    /**
     * @param mixed $xml_string
     *
     * @return null|false|string
     */
    public static function detectStringEncoding($xml_string)
    {
        $output = [];
        \exec(\sprintf("echo '%s' | file -i - ", $xml_string), $output);

        if (isset($output[0])) {
            $ex = \explode('charset=', $output[0]);

            return isset($ex[1]) ? \mb_strtoupper(\str_replace('us-ascii', 'ASCII', $ex[1])) : null;
        }

        return null;
    }

    private function mapCsvToArray(string $csvString): Collection
    {
        $cleancontents = \explode("\n", \trim($csvString));
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
    }

    /**
     * Undocumented function.
     *
     * @return string
     */
    private function representar(string $rut_empresa)
    {
        if (self::$authenticatedOnSii) {
            if ($this->representandoA === $rut_empresa) {
                return $this->representandoA;
            }
            $this->clear();
            $this->clearRepresentacion();
        }

        // If already authenticated, this method is a no-op
        $this->authOnSii();
        [$rutEmpresa, $dvEmpresa] = \explode('-', $rut_empresa);

        try {
            //dump(['self::$certpaths path: ' => $certpaths]);
            $reqOpts = tap(\array_merge($this->getCertPaths(), [
                'headers' => [
                    'Origin' => 'https://herculesr.sii.cl',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer' => 'https://herculesr.sii.cl/cgi_AUT2000/admRPDOBuild.cgi',
                ],
                'form_params' => ['RUT_RPDO' => $rutEmpresa, 'APPLS' => 'RPETC'],
            ]), static fn ($reqOptions) => null/*kdump($reqOptions)*/);
            $response = $this->sendSiiRequest(
                'POST',
                'https://herculesr.sii.cl/cgi_AUT2000/admRepresentar.cgi',
                $reqOpts
            );

            $representarDOM = Str::of($response->getBody()->getContents());

            if (!$representarDOM->contains('REPRESENTACIÓN REALIZADA')) {
                throw new \Exception('NO SE PUDO REPRESENTAR A ' . $rut_empresa);
            }
            $representandoA = '';
            $div = $representarDOM->after('REPRESENTACIÓN REALIZADA</h2>')
                ->before('<p><strong>En las aplicaciones')
                ->afterLast('<p>')
                ->beforeLast('</p>');

            $crawler = new Crawler($div->__toString());
            $representandoRUT = ($crawler->text());

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
            kdump(ExceptionHelper::normalizeException($e));

            return $e->getMessage();
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
        $tablaContenidoHTML = Str::of(($detalleConsultaDOM))->afterLast('<table')
            ->before('</table')
            ->prepend('<table ')->append('</table>')->__toString();

        try {
            $crawler = new Crawler($tablaContenidoHTML);

            $content = [];

            if (!$crawler) {
                return [
                    $detalleConsultaDOM, null,
                ];
            }

            $rows = $crawler->filter('tr');

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
            kdump($e);
        }

        return [
            $detalleConsultaDOM, null,
        ];
    }

    private function getCesionesFromHTML(Crawler $tableHtml, string $rut_empresa): Collection
    {
        $tableHtml = $tableHtml->filter('tbody > tr')->reduce(static function ($tr) {
            $tds = $tr->filter('td');

            return $tds->count() >= 9; //&& $tds->eq(2)->text() === $rut_empresa;
        });

        $cesiones = collect($tableHtml->each(static function ($tr) {
            $tds = $tr->filter('td');

            $tipoDocText = $tds->eq(7)->text();
            $tipoDoc = 'FACTURA ELECTRONICA' === $tipoDocText ? 33 : 34;
            $rutEmisor = $tds->eq(6)->text();

            $folio = $tds->eq(8)->text();
            $dte_id = \sprintf('%s_%d_%d', $rutEmisor, $tipoDoc, $folio);
            $sii_id = Str::of($tds->eq(0)->html())->afterLast('="')->beforeLast('">')->__toString();

            return [
                'sii_id' => (int) $sii_id,
                'dte_id' => $dte_id,
                'rut_cedente' => $tds->eq(1)->text(),
                'rut_cesionario' => $tds->eq(2)->text(),
                'fecha_cesion' => $tds->eq(4)->text(),
                'fecha_emision' => $tds->eq(9)->text(),
                'rut_deudor' => $tds->eq(3)->text(),
                'tipo_docText' => $tipoDocText,
                'rut_emisor' => $rutEmisor,
                'tipo_doc' => $tipoDoc,
                'folio' => $folio,
                'monto_cesion' => (float) (Str::of($tds->eq(5)->text())->replace(["'", 'FormateDinero(', ');'], '')->__toString()),
                'monto_total' => (float) (Str::of($tds->eq(10)->text())->replace(["'", 'FormateDinero(', ');'], '')->__toString()),
            ];
        }));

        return $cesiones;
    }
}
