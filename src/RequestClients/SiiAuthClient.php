<?php

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients;

use CTOhm\SiiAsyncClients\RequestClients\AbstractCrawlers\RequestClientInterface;
use CTOhm\SiiAsyncClients\RequestClients\AbstractCrawlers\SiiAbstractCrawler;
use CTOhm\SiiAsyncClients\RequestClients\Structures\CertificatesObjectInterface;
use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use CTOhm\SiiAsyncClients\Util\ExceptionHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @template T
 */
class SiiAuthClient extends SiiAbstractCrawler implements RequestClientInterface
{
    const BASE_URL = 'https://www1.sii.cl/cgi-bin/Portal001';
    /**
     * @var int
     */
    const TIPO_EMITIDO = 1;
    /**
     * @var int
     */
    const TIPO_RECIBIDO = 2;

    /**
     * @var null|false|resource
     */
    public static $tmp_file_cert;

    /**
     * @var null|false|resource
     */
    public static $tmp_file_pkey;

    /**
     * @var null|false|resource
     */
    public static $tmp_file_extracerts;

    /**
     * @var bool
     */
    public static $authenticatedOnSii = false;

    /**
     * @var null|string
     */
    public static $tempFolder;

    /**
     * @var null|array
     */
    public static $certpaths;

    /**
     * @var false
     */
    public static $debug = false;

    public static string $common_uri = 'https://www1.sii.cl/cgi-bin/Portal001';

    /**
     * @var null|\GuzzleHttp\Client
     */
    public static $client;

    protected int $totaltime = 0;

    /**
     * @var null|\GuzzleHttp\Cookie\CookieJar
     */
    protected static $cookiejar;

    /**
     * @var null|callable
     *
     * @psalm-var callable(callable):callable|null
     */
    protected static $history;

    /**
     * @var array
     */
    protected static $container = [];

    protected static $rut_empresa;

    protected static $tipo_documento;

    protected static CertificatesObjectInterface $certs;

    /**
     * Constructs a new instance.
     */
    public function __construct(?SiiSignatureInterface $siiSignature = null, array $clientOptions = [])
    {
        self::$tempFolder = \sys_get_temp_dir();
        self::$certs = $siiSignature->getCerts();

        self::$client = $this->getClient($clientOptions);
        //// dump(self::$tempFolder);
    }

    /**
     * { function_description }.
     *
     * @param SiiSignatureInterface $siiSignature The firma electronica
     */
    public function recreate(SiiSignatureInterface $siiSignature, array $clientOptions = []): void
    {
        self::$certs = $siiSignature->getCerts();

        self::$client = $this->getClient($clientOptions);

        $clientOptions['cookies'] = $clientOptions['cookies'] ?? [];

        if ($clientOptions['cookies'] instanceof CookieJar) {
            self::$cookiejar = $clientOptions['cookies'];
        } else {
            self::$cookiejar = null;

            self::$cookiejar = $this->getCookieJar();
        }
        static::$authenticatedOnSii = false;
        self::$client = null;
        self::$client = $this->getClient($clientOptions);
        // kdump(self::$tempFolder);
    }

    /**
     * { function_description }.
     *
     * @param string $rut_empresa The rut empresa
     *
     * @return null|object ( description_of_the_return_value )
     */
    public function selecionaEmpresa($rut_empresa, bool $debug = false): ?object
    {
        $this->authOnSii();
        $response = $this->sendSiiRequest(
            'POST',
            self::getUrl('mipeSelEmpresa.cgi'),
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer' => self::getUrl('mipeSelEmpresa.cgi?DESDE_DONDE_URL=OPCION%3D2%26TIPO%3D4'),
                ],
                'form_params' => [
                    //'DESDE_DONDE_URL' => 'OPCION%3D2%26TIPO%3D4',
                    'RUT_EMP' => $rut_empresa,
                ],
                'debug' => $debug,
            ]
        );
        self::$rut_empresa = $rut_empresa;

        return $response;
    }

    /**
     * Gets the url.
     *
     * @param string      $path   The path
     * @param null|string $prefix The prefix
     *
     * @return string the url
     */
    public static function getUrl(string $path = '', ?string $prefix = null): string
    {
        //return sprintf('%s/%s', self::$base_url, $path);
        return \sprintf('%s/%s', $prefix ?? self::$common_uri, $path);
    }

    /**
     * Authenticates against the SII.
     *
     * @throws \Exception (description)
     *
     * @return CookieJar ( description_of_the_return_value )
     */
    public function authOnSii(array $options = ['debug' => false]): CookieJar
    {
        $cookies = $options['cookies'] ?? $this->getCookieJar();

        if (static::$authenticatedOnSii) {
            return $cookies;
        }

        //dump($certpaths);
        $referencia = 'https://misiir.sii.cl/cgi_misii/siihome.cgi';

        $options = \array_merge($options, self::getCertFiles(), [
            'headers' => [
                'Origin' => 'https://zeusr.sii.cl',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Connection' => 'close',
                'Referer' => "https://zeusr.sii.cl/AUT2000/InicioAutenticacion/IngresoCertificado.html?{$referencia}",
            ],
            'retry' => 1,
            'form_params' => ['referencia' => $referencia],
            'cookies' => $cookies,
        ]);

        $response = $this->sendSiiRequest('POST', 'https://herculesr.sii.cl/cgi_AUT2000/CAutInicio.cgi', $options);

        if (200 === $response->getStatusCode()) {
            static::$authenticatedOnSii = true;
        } else {
            throw new \Exception(\sprintf('Response from SII has header %s', $response->getStatusCode()));
        }

        return $cookies;
    }

    /**
     * Undocumented function.
     *
     * @return array{token:string}|null array representation of the SiiToken
     */
    public function getToken(bool $debug = false): ?array
    {
        $siiToken = Cache::remember('siiToken', 300, function () {
            $cookies = $this->authOnSii(['stats' => false]);
            $tokencookie = $cookies->getCookieByName('token');

            if (null === $tokencookie) {
                return;
            }

            return ['token' => $tokencookie->getValue()];
        });

        if (\is_array($siiToken) && \array_key_exists('token', $siiToken)) {
            $response = $this->sendSiiRequest(
                'GET',
                'https://herculesr.sii.cl/cgi_AUT2000/admRPDOBuild.cgi',
                \array_merge($this->getCertFiles(), [
                    'headers' => [
                        'Origin' => 'https://zeusr.sii.cl',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Connection' => 'close',
                        'Referer' => 'https://herculesr.sii.cl/cgi_AUT2000/CAutInicio.cgi',
                    ],
                    'cookies' => $this->getCookieJar(),
                ])
            );
            $contents = \implode(
                \PHP_EOL,
                \array_slice(
                    \explode(
                        \PHP_EOL,
                        $response->getBody()->getContents()
                    ),
                    0,
                    10
                )
            );

            if (\mb_strpos($contents, 'SELECCIONE A QUIEN REPRESENTAR') !== false) {
                static::$authenticatedOnSii = true;
            } else {
                $this->authOnSii(['stats' => false]);

                if ($tokenCookie = $this->getCookieJar()->getCookieByName('token')) {
                    Cache::set('siiToken', ['token' => $tokenCookie->getValue()], 300);
                }
            }
        }

        return $siiToken;
    }

    /**
     * Gets the cookie jar.
     *
     * @return \GuzzleHttp\Cookie\CookieJar the cookie jar
     */
    public function getCookieJar(array $clientOptions = []): \GuzzleHttp\Cookie\CookieJar
    {
        $clientOptions['cookies'] = $clientOptions['cookies'] ?? [];

        if (!static::$cookiejar) {
            // // dump(['CookieJar existente'=>self::$cookiejar->getCookieByName('token')]);
            if ($clientOptions['cookies'] instanceof CookieJar) {
                static::$cookiejar = $clientOptions['cookies'];
            } else {
                // // dump('new cookiejar');
                static::$cookiejar = new \GuzzleHttp\Cookie\CookieJar();
            }
        }

        return static::$cookiejar;
    }

    /**
     * Gets the client.
     *
     * @return \GuzzleHttp\Client the client
     */
    public function getClient(array $clientOptions = []): \GuzzleHttp\Client
    {
        if (!self::$client) {
            static::$history = Middleware::history(static::$container);

            $multiHandler = app(CurlMultiHandler::class);
            //// dump([__METHOD__ => $multiHandler]);
            $handlerStack = HandlerStack::create($multiHandler);
            // or $handlerStack = HandlerStack::create($mock); if using the Mock handler.

            // Add the history middleware to the handler stack.
            //$handlerStack->push(self::$history);
            $handlerStack->push(static::debugRequestHeaders());

            $clientOptions['cookies'] = $clientOptions['cookies'] ?? $this->getCookieJar($clientOptions);

            $clientOptions = \array_merge([
                'handler' => $handlerStack,
                'base_uri' => static::BASE_URL,
                'curl.options' => [
                    \CURLOPT_SSLVERSION => \CURL_SSLVERSION_TLSv1_2,
                ],
            ], $clientOptions);

            self::$client = new \GuzzleHttp\Client($clientOptions);
        }

        return self::$client;
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
     * Clears the client and its cookies.
     */
    public function clear(): void
    {
        $this->getCookieJar()->clear();
        static::$authenticatedOnSii = false;
    }

    /**
     * @return CertificatesObjectInterface
     */
    protected function getCerts()
    {
        return static::$certs;
    }

    /**
     * Sends a sii request.
     *
     * @param string $verb     The verb (GET, POST)
     * @param string $url      The url
     * @param array  $options  Opciones: headers, query string, etc
     * @param bool   $printReq Imprimit el request en la consola, para debug
     *
     * @return null|ResponseInterface stdClass con statusCode y contenido re la respueata
     */
    protected function sendSiiRequest($verb, $url, $options = [], $printReq = false): ?ResponseInterface
    {
        $options['headers'] = \array_merge(
            [
                //'Connection' => 'keep-alive',
                'Cache-Control' => 'max-age=0',
                'Connection' => 'close',
                'Upgrade-Insecure-Requests' => '1',
            ],
            $options['headers'] ?? []
        );
        $reqOptions = \array_merge(['force_ip_resolve' => 'v4', 'cookies' => $this->getCookieJar()], $options);

        if ($options['stats'] ?? null) {
            $reqOptions['on_stats'] = static function (\GuzzleHttp\TransferStats $stats): void {
                kdump($stats->getEffectiveUri() . ' : ' . $stats->getTransferTime());
            };
        }

        $reqParams = [
            $verb => $url, 'options' => Arr::except($reqOptions, ['cookies']),
        ];

        if ($printReq) {
            // dump($reqParams);
        }
        //dump($reqOptions);
        $dummyResponse = new Response(500);

        try {
            return self::$client->request($verb, $url, $reqOptions);
        } catch (ConnectException $e) {
            // dump(ExceptionHelper::normalizeException($e));

            if ($options['retry'] ?? 0) {
                --$options['retry'];
                $options['delay'] = config('sii.default_request_delay_ms') * 10;  // generous backoff

                return $this->sendSiiRequest($verb, $url, $options);
            }

            return new Response(500, [], $e->getMessage());
        } catch (BadResponseException $e) {
            // dump(ExceptionHelper::normalizeException($e));
            $res = $e->getResponse();

            return $res ?? $dummyResponse;
        } catch (\Exception $e) {
            // dump(ExceptionHelper::normalizeException($e));

            return $dummyResponse;
        }

        return $res ?? $dummyResponse;
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
                    return $handler($request, $options);
                }

                foreach ($request->getHeaders() as $headername => $headervalue) {
                    $headers[$headername] = $headervalue[0];
                }
                /* dump([
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
                ]);*/

                return $handler($request, $options);
            };
        };
    }

    /**
     * @return string
     */
    private function fillAndRetrieveTemporaryFile(...$contents)
    {
        $tmp_file_cert = \tmpfile();

        foreach ($contents as $content) {
            \fwrite($tmp_file_cert, $content);
        }

        return (\stream_get_meta_data($tmp_file_cert))['uri'];
    }
}
