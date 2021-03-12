<?php

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients;

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
class SiiAuthClient
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
     * @var array
     */
    protected static $certs;

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

    /**
     * Constructs a new instance.
     */
    public function __construct(?SiiSignatureInterface $siiSignature = null, array $clientOptions = [])
    {
        self::$tempFolder = \sys_get_temp_dir();

        self::$client = $this->getClient($clientOptions);
        // dump(self::$tempFolder);
        static::$certs = $siiSignature->getCerts()->toArray();
    }

    /**
     * Authenticates against the SII.
     *
     * @throws \Exception (description)
     *
     * @return null|object ( description_of_the_return_value )
     */
    public function authOnSii(array $options = ['debug' => false])
    {
        if (static::$authenticatedOnSii) {
            return;
        }
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        //  dump(['authenticating against SII'=>$stack,'cookiejar'=>$this->getCookieJar($options)]);
        $certpaths = static::getCertFiles();
        //dump($certpaths);
        $referencia = 'https://misiir.sii.cl/cgi_misii/siihome.cgi';
        $cookies = $options['cookies'] ?? $this->getCookieJar();
        $options = \array_merge($options, [
            'headers' => [
                'Origin' => 'https://zeusr.sii.cl',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Connection' => 'close',
                'Referer' => "https://zeusr.sii.cl/AUT2000/InicioAutenticacion/IngresoCertificado.html?{$referencia}",
            ],
            'retry' => 1,
            'form_params' => ['referencia' => $referencia],
            'cert' => $certpaths['client'],
            'ssl_key' => $certpaths['key'],
            'verify' => $certpaths['ca'] ?? null,
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
     * @return array{value:string}|null array representation of the SiiToken
     */
    public function getToken(bool $debug = false): ?array
    {
        $siiToken = Cache::remember('siiToken', 300, function () {
            $this->authOnSii(['stats' => false]);

            $tokencookie = $this->getCookieJar()->getCookieByName('token');

            if (null === $tokencookie) {
                return;
            }

            return ['token' => $tokencookie->getValue()];
        });

        if (\is_array($siiToken) && \array_key_exists('token', $siiToken)) {
            $certpaths = static::getCertFiles();

            $response = $this->sendSiiRequest('GET', 'https://herculesr.sii.cl/cgi_AUT2000/admRPDOBuild.cgi', [
                'headers' => [
                    'Origin' => 'https://zeusr.sii.cl',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Connection' => 'close',
                    'Referer' => 'https://herculesr.sii.cl/cgi_AUT2000/CAutInicio.cgi',
                ],

                'cert' => $certpaths['client'],
                'ssl_key' => $certpaths['key'],
                'verify' => $certpaths['ca'] ?? null,
                'cookies' => $this->getCookieJar(),
            ]);
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

                if ($debug) {
                    dump([__CLASS__ => \sprintf('using cached token %s', $siiToken['token'])]);
                }
            } else {
                $this->authOnSii(['stats' => false]);

                if ($tokenCookie = $this->getCookieJar()->getCookieByName('token')) {
                    if ($debug) {
                        dump(
                            [
                                __CLASS__ => \sprintf('renewed token %s', $tokenCookie->getValue()),
                            ]
                        );
                    }

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
            //  dump(['CookieJar existente'=>self::$cookiejar->getCookieByName('token')]);
            if ($clientOptions['cookies'] instanceof CookieJar) {
                static::$cookiejar = $clientOptions['cookies'];
            } else {
                //  dump('new cookiejar');
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
            // dump([__METHOD__ => $multiHandler]);
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
     * @return array
     */
    protected function getCerts()
    {
        return static::$certs;
    }

    /**
     * Gets the cert files.
     *
     * @return array{client:string,key:string,ca:string|null} array of paths to the cert files
     */
    protected static function getCertFiles(): array
    {
        if (!static::$certpaths) {
            static::$tmp_file_cert = \tmpfile(); //\fopen(storage_path('tmp/tmp_file_cert.pem'), 'wb');
            \fwrite(static::$tmp_file_cert, static::$certs['cert']);
            $tmp_file_cert_path = \stream_get_meta_data(static::$tmp_file_cert)['uri'];

            static::$tmp_file_pkey = \tmpfile(); // \fopen(storage_path('tmp/tmp_file_pkey.key'), 'wb');
            \fwrite(static::$tmp_file_pkey, static::$certs['pkey']);
            $tmp_file_pkey_path = \stream_get_meta_data(static::$tmp_file_pkey)['uri'];
            static::$certpaths = [
                'client' => $tmp_file_cert_path,
                'key' => $tmp_file_pkey_path,
            ];

            self::$certpaths['ca'] = config('sii-clients.cacert_pemfile');

            if (\array_key_exists('extracerts', static::$certs)) {
                static::$tmp_file_extracerts = \tmpfile();

                foreach (static::$certs['extracerts'] as $cacert) {
                    \fwrite(static::$tmp_file_extracerts, $cacert);
                }
                $tmp_file_extracerts_path = \stream_get_meta_data(static::$tmp_file_extracerts)['uri'];
                static::$certpaths['ca'] = $tmp_file_extracerts_path;
            }
        }

        return self::$certpaths;
    }

    /**
     * Clears the client and its cookies.
     */
    protected function clear(): void
    {
        $this->getCookieJar()->clear();
        static::$authenticatedOnSii = false;
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
            dump($reqParams);
        }
        //dump($reqOptions);
        $dummyResponse = new Response(500);

        try {
            return self::$client->request($verb, $url, $reqOptions);
        } catch (ConnectException $e) {
            dump(ExceptionHelper::normalizeException($e));

            if ($options['retry'] ?? 0) {
                --$options['retry'];
                $options['delay'] = config('sii.default_request_delay_ms') * 10;  // generous backoff

                return $this->sendSiiRequest($verb, $url, $options);
            }

            return new Response(500, [], $e->getMessage());
        } catch (BadResponseException $e) {
            dump(ExceptionHelper::normalizeException($e));
            $res = $e->getResponse();

            return $res ?? $dummyResponse;
        } catch (\Exception $e) {
            dump(ExceptionHelper::normalizeException($e));

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
                dump([
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

                return $handler($request, $options);
            };
        };
    }
}
