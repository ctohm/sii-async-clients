<?php

declare(strict_types=1);

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
    public const BASE_URL = 'https://www1.sii.cl/cgi-bin/Portal001';
    /**
     * @var int
     */
    public const TIPO_EMITIDO = 1;
    /**
     * @var int
     */
    public const TIPO_RECIBIDO = 2;

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

    protected static $siiSignature;

    private int $totaltime = 0;

    /**
     * @var null|\GuzzleHttp\Cookie\CookieJar
     */
    private static $cookiejar;

    /**
     * @var array
     */
    private static $certs;

    /**
     * @var null|callable
     *
     * @psalm-var callable(callable):callable|null
     */
    private static $history;

    /**
     * @var array
     */
    private static $container = [];

    private static $rut_empresa;

    private static $tipo_documento;

    /**
     * Constructs a new instance.
     */
    public function __construct(SiiSignatureInterface $siiSignature, array $clientOptions = [])
    {
        self::$common_uri = $clientOptions['baseURL'] ?? self::$common_uri;
        self::$siiSignature = $siiSignature;

        self::$tempFolder = \sys_get_temp_dir();

        self::$client = $this->getClient($clientOptions);
        // dump(self::$tempFolder);
        self::$certs = [
            'pkey' => $siiSignature->getPrivateKey(),
            'cert' => $siiSignature->getPublicKey(),
            'extracerts' => $siiSignature->getExtraCerts(),
        ];
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
        if (self::$authenticatedOnSii) {
            return;
        }
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        //  dump(['authenticating against SII'=>$stack,'cookiejar'=>$this->getCookieJar($options)]);
        $certpaths = self::getCertFiles();
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
            self::$authenticatedOnSii = true;
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
            $certpaths = self::getCertFiles();

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
                self::$authenticatedOnSii = true;

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
    public function getCookieJar(array $clientOptions = []): CookieJar
    {
        $clientOptions['cookies'] = $clientOptions['cookies'] ?? [];

        if (!self::$cookiejar) {
            //  dump(['CookieJar existente'=>self::$cookiejar->getCookieByName('token')]);
            if ($clientOptions['cookies'] instanceof CookieJar) {
                self::$cookiejar = $clientOptions['cookies'];
            } else {
                //  dump('new cookiejar');
                self::$cookiejar = new \GuzzleHttp\Cookie\CookieJar();
            }
        }

        return self::$cookiejar;
    }

    /**
     * Gets the client.
     *
     * @return \GuzzleHttp\Client the client
     */
    public function getClient(array $clientOptions = []): Client
    {
        if (!self::$client) {
            self::$history = Middleware::history(self::$container);

            $multiHandler = app(CurlMultiHandler::class);

            $handlerStack = HandlerStack::create($multiHandler);

            // Add the history middleware to the handler stack.
            //$handlerStack->push(self::$history);
            $handlerStack->push(self::debugRequestHeaders());

            $clientOptions['cookies'] = $clientOptions['cookies'] ?? $this->getCookieJar($clientOptions);

            $clientOptions = \array_merge([
                'handler' => $handlerStack,
                'base_uri' => static::BASE_URL,
                'curl.options' => [
                    \CURLOPT_SSLVERSION => \CURL_SSLVERSION_TLSv1_2,
                ],
            ], $clientOptions);
            //kdump($clientOptions);

            self::$client = new \GuzzleHttp\Client($clientOptions);
        }

        return self::$client;
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
                $options['delay'] = config('sii-clients.default_request_delay_ms') * 10;  // generous backoff

                return $this->sendSiiRequest($verb, $url, $options);
            }
            $res = new Response(500, [], $e->getMessage());

            return $res ?? $dummyResponse;
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
     * Gets the cert files.
     *
     * @return array{client:string,key:string,ca:string|null} array of paths to the cert files
     */
    protected static function getCertFiles(): array
    {
        if (!self::$certpaths) {
            self::$tmp_file_cert = \fopen(storage_path('tmp/tmp_file_cert.pem'), 'wb');
            \fwrite(self::$tmp_file_cert, self::$certs['cert']);
            $tmp_file_cert_path = \stream_get_meta_data(self::$tmp_file_cert)['uri'];

            self::$tmp_file_pkey = \fopen(storage_path('tmp/tmp_file_pkey.key'), 'wb');
            \fwrite(self::$tmp_file_pkey, self::$certs['pkey']);
            $tmp_file_pkey_path = \stream_get_meta_data(self::$tmp_file_pkey)['uri'];
            self::$certpaths = [
                'client' => $tmp_file_cert_path,
                'key' => $tmp_file_pkey_path,
            ];

            if (\array_key_exists('extracerts', self::$certs)) {
                self::$tmp_file_extracerts = \fopen(storage_path('tmp/tmp_file_extracerts.pem'), 'wb');

                foreach (self::$certs['extracerts'] as $cacert) {
                    \fwrite(self::$tmp_file_extracerts, $cacert);
                }
                $tmp_file_extracerts_path = \stream_get_meta_data(self::$tmp_file_extracerts)['uri'];
                self::$certpaths['ca'] = $tmp_file_extracerts_path;
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
        self::$authenticatedOnSii = false;
    }

    /**
     * @return \Closure
     *
     * @psalm-return \Closure(callable):\Closure(RequestInterface, array=):mixed
     */
    protected static function debugRequestHeaders()
    {
        return /**
         * @psalm-return \Closure(\Psr\Http\Message\RequestInterface, array=):\Closure(\Psr\Http\Message\RequestInterface, array=):mixed
         */
        static function (callable $handler): \Closure {
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
