<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients;

use CTOhm\SiiAsyncClients\RequestClients\Structures\DteCedibleResponse;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EventosHistoricosParameters;
use CTOhm\SiiAsyncClients\RequestClients\Structures\RetrievesEventosHistoricosInterface;
use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use CTOhm\SiiAsyncClients\Util\ExceptionHelper;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * @template T
 */
class RestClient extends SiiAuthClient implements RetrievesEventosHistoricosInterface
{
    /**
     * @var string
     */
    public const BASE_NAMESPACE = 'cl.sii.sdi.lob.diii.registrorechazodtej6.data.api.interfaces.FacadeService';
    /**
     * @var string
     */
    public const BASE_URL = 'https://www4.sii.cl/registrorechazodtej6ui';
    /**
     * @var int
     */
    public const TIPO_EMITIDO = 1;
    /**
     * @var int
     */
    public const TIPO_RECIBIDO = 2;

    /**
     * @var false
     */
    public static $debug = false;

    /**
     * @var null|\GuzzleHttp\Client
     */
    public static $client;

    /**
     * @var array<array-key, mixed>
     */
    public static $CommonOptions = [
        'headers' => [
            'Origin' => 'https://www4.sii.cl',
            'Content-Type' => 'application/json',
            'Referer' => self::BASE_URL,
        ],
    ];

    public static string $common_uri = 'https://www4.sii.cl/registrorechazodtej6ui/services/data/facadeService';

    /**
     * @var null|\GuzzleHttp\Cookie\CookieJar
     */
    protected static $cookiejar;

    /**
     * @var array<array-key, mixed>
     */
    protected static $certs;

    protected static $history;

    /**
     * @var array
     */
    protected static $container = [];

    protected static $rut_empresa;

    protected static $tipo_documento;

    public function __construct(SiiSignatureInterface $siiSignature, array $clientOptions = [])
    {
        self::$common_uri = $clientOptions['baseURL'] ?? self::$common_uri;
        self::$CommonOptions['delay'] = config('sii-clients.default_request_delay_ms'); // milliseconds

        parent::__construct($siiSignature, $clientOptions);
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
     * Gets the namespace.
     *
     * @param string $method The method
     *
     * @return string the namespace
     */
    public static function getNamespace($method = '')
    {
        return \sprintf('%s/%s', self::BASE_NAMESPACE, $method);
    }

    /**
     * { function_description }.
     */
    public function consultarDocDteCedible(
        Structures\EventosHistoricosParameters $requestPayload,
        array $options = ['debug' => false]
    ): DteCedibleResponse {
        $cookies = $this->authOnSii();
        $tokencookie = $cookies->getCookieByName('token');
        $token = '';
        //self::$tipo_documento = $tipo_doc;
        if ($tokencookie) {
            $token = $tokencookie->getValue();
        }

        $json_values = self::getJsonPayload($requestPayload, $token);

        $url = $this->getUrl('consultarDocDteCedible', self::$common_uri);
        $response = $this->sendSiiRequest(
            'POST',
            $url,
            \array_merge(self::$CommonOptions, $options, [
                'json' => $json_values,
                'cookies' => $this->getCookieJar(),
            ])
        );

        if (!($response instanceof ResponseInterface)) {
            throw new InvalidArgumentException('Couldnt get a response from SII consultarDocDteCedible');
        }
        $contents = \json_decode($response->getBody()->getContents(), true);

        return new DteCedibleResponse(Collection::wrap($contents['data'] ?? []));
    }

    /**
     * { function_description }.
     *
     * @param mixed $options
     *
     * @return PromiseInterface ( description_of_the_return_value )
     */
    public function listarEventosHistDoc(
        Structures\EventosHistoricosParameters $requestPayload,
        $options = []
    ): PromiseInterface {
        $cookies = $this->authOnSii();

        try {
            $tokencookie = $cookies->getCookieByName('token');

            $token = '';

            if ($tokencookie) {
                $token = $tokencookie->getValue();
            }

            $json_values = self::getJsonPayload($requestPayload, $token);
            $url = $this->getUrl('validarAccesoReceptor', self::$common_uri);
            $mergedOpts = \array_merge(self::$CommonOptions, $options, [
                'json' => $json_values,
                'cookies' => $cookies,
            ]);

            return $this->sendSiiRequestAsync(
                'POST',
                $url,
                $mergedOpts,
            )
                ->otherwise(
                    static function (\Exception $errAsync) {
                        dump(ExceptionHelper::normalizeException($errAsync));

                        return $errAsync;
                    }
                )
                ->then(static function ($res) {
                    $contents = \json_decode($res->getBody()->getContents(), true);
                    $data = $contents['data'] ?? [];

                    return (new Structures\EventosHistoricosResponse(collect($data)->toArray()))->jsonSerialize();
                });
        } catch (\Exception $e) {
            dump(ExceptionHelper::normalizeException($e));

            return new Promise(static fn () => ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return (int|null|string)[][]
     *
     * @psalm-return array{metaData: array{namespace: string, conversationId: string, transactionId: string, page: null}, data: array{dvEmisor: string, dvToken: string, folio: int|null, rutEmisor: string, rutToken: null|string, tipoDoc: int|null}}
     */
    protected static function getJsonPayload(EventosHistoricosParameters $requestPayload, string $token): array
    {
        return [
            'metaData' => [
                'namespace' => self::getNamespace('validarAccesoReceptor'),
                'conversationId' => $token,
                'transactionId' => $requestPayload->transaction_id,
                'page' => null,
            ],
            'data' => [
                'dvEmisor' => $requestPayload->dvEmisor,
                'dvToken' => $requestPayload->dvToken,
                'folio' => $requestPayload->folio,
                'rutEmisor' => $requestPayload->rutEmisor,
                'rutToken' => $requestPayload->rutToken,
                'tipoDoc' => $requestPayload->tipoDoc,
            ],
        ];
    }

    /**
     * Sends a sii request.
     *
     * @param string                  $verb    The verb (GET, POST)
     * @param string                  $url     The url
     * @param array<array-key, mixed> $options Opciones: headers, query string, etc
     *
     * @return PromiseInterface promesa de una respuesta con statusCode y contenido re la respueata
     */
    protected function sendSiiRequestAsync($verb, $url, $options = []): PromiseInterface
    {
        $options['headers'] = \array_merge(
            [
                'Connection' => 'keep-alive',
                'Cache-Control' => 'max-age=0',
                'Upgrade-Insecure-Requests' => '1',
            ],
            $options['headers']
        );
        $reqOptions = \array_merge($options, ['cookies' => self::$cookiejar]);
        $reqParams = [
            $verb => $url, 'options' => Arr::except($reqOptions, ['cookies']),
        ];

        if ($options['debug'] ?? null) {
            dump($reqParams);
        }
        //kdump($reqOptions);
        $dummyResponse = (new Response(500));

        try {
            return $this->getClient()->requestAsync($verb, $url, $reqOptions)->otherwise(static function ($errAsync) use ($dummyResponse) {
                return $errAsync->getResponse() ?? $dummyResponse;
            });
        } catch (ConnectException $e) {
            dump(ExceptionHelper::normalizeException($e));

            if ($options['retry'] ?? 0) {
                --$options['retry'];
                $options['delay'] = config('sii-clients.default_request_delay_ms') * 10; // generous backoff

                return $this->sendSiiRequestAsync($verb, $url, $options);
            }
            $res = new Response(500, [], $e->getMessage());

            return new FulfilledPromise($res ?? $dummyResponse);
        } catch (BadResponseException $e) {
            dump(ExceptionHelper::normalizeException($e));
            $res = $e->getResponse();

            return new FulfilledPromise($res ?? $dummyResponse);
        } catch (\Exception $e) {
            dump(ExceptionHelper::normalizeException($e));

            return new FulfilledPromise($dummyResponse);
        }

        return new FulfilledPromise($res ?? $dummyResponse);
    }
}
