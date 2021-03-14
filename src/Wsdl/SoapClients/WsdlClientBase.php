<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\SoapClients;

use CTOhm\SiiAsyncClients\RequestClients\Structures\SiiSignatureInterface;
use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\AsyncSoapClient;
use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\SoapClientFactory;
use CTOhm\SiiAsyncClients\Wsdl\TokenGetterClient;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Log;
use Throwable;
use WsdlToPhp\PackageBase\AbstractSoapClientBase;
use Meng\AsyncSoap\SoapClientInterface;


/**
 * This class stands for Get ServiceType.
 * @template T of \Meng\AsyncSoap\SoapClientInterface 
 * @psalm-template T of \Meng\AsyncSoap\SoapClientInterface
 * @psalm-internal CTOhm\SiiAsyncClients\Wsdl
 */
abstract class WsdlClientBase extends AbstractSoapClientBase
{
    public const LOCAL_FILE = 'local_file';
    public const WSDL_SLUG = 'slug';

    protected static ?string $siiToken = null;

    protected array $mergedClientOptions = [];

    /**
     * 
     * @psalm-var T
     * @var T
     */
    protected static  $asyncSoapClient = null;
    /**
     * Undocumented variable.
     *
     * @var array<array-key,\Meng\AsyncSoap\SoapClientInterface>
     */
    protected static array $asyncSoapClientsArray = [];


    /**
     * @psalm-return T
     * @return T
     */
    final public function getAsyncSoapClient(string $class = AsyncSoapClient::class): SoapClientInterface
    {
        $clientOptions = $this->mergedClientOptions;
        $localWsdl = $clientOptions[self::LOCAL_FILE];


        if (self::$asyncSoapClientsArray[$localWsdl] ?? null) {
            return self::$asyncSoapClientsArray[$localWsdl];
        }

        if (!$soapToken = $this->getTokenIfNotPresent()) {
            throw new Exception('No soapToken was passed to the constructor options and we couldnt retrieve a new one');
        }

        $clientOptions['classmap'] = $clientOptions[self::WSDL_CLASSMAP];

        $factory = new SoapClientFactory();
        if ($clientOptions['restClient'] ?? null) {
            $clientGuzzle = $clientOptions['restClient']->getClient();
        } else {
            $clientOptions['cookies'] = CookieJar::fromArray(['TOKEN' => $soapToken], 'sii.cl');
            $multiHandler = app(CurlMultiHandler::class);
            $clientOptions['handler'] = HandlerStack::create($multiHandler);
            $clientGuzzle = new Client(\array_merge(['base_url' => $clientOptions[self::WSDL_URL]], $clientOptions));
        }


        $soapOptions = array_merge([
            'cache_wsdl' => config('sii.cache_policy'), //\WSDL_CACHE_NONE, //  \WSDL_CACHE_DISK,
            'trace' => true,
            'exceptions' => true,
            'keep_alive' => false,
        ], $clientOptions);

        self::$asyncSoapClientsArray[$localWsdl] = $factory->create($clientGuzzle, $localWsdl, $soapOptions);
        self::$asyncSoapClientsArray[$localWsdl]->__setCookie('TOKEN', $soapToken);
        return  self::$asyncSoapClientsArray[$localWsdl];
    }


    final public function getTokenIfNotPresent(): string
    {
        if (self::$siiToken) {
            return self::$siiToken;
        }

        if (app()->bound(SiiSignatureInterface::class)) {
            $siiSignature = app()->make(SiiSignatureInterface::class);
            self::$siiToken = app(TokenGetterClient::class)->getCachedOrRenewedToken($siiSignature);

            return self::$siiToken;
        }

        throw new Exception('No te ha definido un token para el SII ni se ha declarado una firma electrónica');
    }

    final public function throwSoapException(Throwable $e): string
    {
        return \sprintf('Error al ejecutar consulta a webservice soap. %s', $e->getMessage());
    }



    /**
     * Starts an attempt loop.
     *
     * @param string     $requestFn   The request function
     * @param null|mixed $args        The arguments
     * @param array      $soapOptions {retryAttempts:int,retriesSoFar:int}     $soapOptions   How many  retries to perform before throwing
     *
     * @return object ( description_of_the_return_value )
     */
    final public function startAttemptLoop(string $requestFn, $args = null, array $soapOptions = [])
    {
        $soapClient = $this->getSoapClient();
        $soapOptions['retryAttempts'] = (int) ($soapOptions['retryAttempts'] ?? 1);
        $soapOptions['retriesSoFar'] = (int) ($soapOptions['retriesSoFar'] ?? 0);

        try {
            $soapResultBody = $args ? \call_user_func_array([
                $soapClient, $requestFn,
            ], $args) : $soapClient->{$requestFn}();

            $this->logSoapResponse($soapResultBody, $requestFn, $args ?? [], $soapOptions);

            return $soapResultBody;
        } catch (\error $e) {
            Log::warning($e);

            throw new \Exception($this->throwSoapException($e));
        } catch (\Exception $e) {
            Log::error($e);

            if ($soapOptions['retriesSoFar'] >= $soapOptions['retryAttempts']) {
                throw new \Exception($this->throwSoapException($e));
            }
        }
        \usleep(50000 * \min(5, 2 ^ $soapOptions['retriesSoFar'])); // wait 0.5 secs, then 1, then 2, then 4, capped at five
        $soapOptions['retriesSoFar'] = $soapOptions['retriesSoFar'] + 1;

        return $this->startAttemptLoop($requestFn, $args, $soapOptions);
    }

    /**
     * Undocumented function.
     *
     * @param mixed $body
     */
    final public function logSoapResponse(
        $body,
        string $requestFn,
        array $args = [],
        array $soapOptions = []
    ): void {
        $soapClient = $this->getSoapClient();
        $dumpRequestBody = $soapOptions['dumpRequestBody'] ?? false;
        $dumpResponseBody = $soapOptions['dumpResponseBody'] ?? false;
        $dumpRequestHeaders = $soapOptions['dumpRequestHeaders'] ?? false;
        $dumpResponseHeaders = $soapOptions['dumpResponseHeaders'] ?? false;

        if (
            'getSeed' !== $requestFn
            && 'getToken' !== $requestFn
            && \get_class($this) !== CrSeedClient::class
            && \get_class($this) !== TokenGetterClient::class
        ) {
            $usedToken = $soapClient ? ($soapClient->__getCookies()['TOKEN'] ?? ['']) : ['NO SOAPCLIENT!!'];
            $debugSoapOutput = collect([
                'requestFn' => $requestFn,
                'usedToken' => $usedToken[0],
                'args' => $args ?? [],
                'body' => $body,
                // 'wsdlLocation'=>$this->getFormatedXml(),
                //'servicio'=>$this->servicio,
                'requestBody' => $dumpRequestBody ? $soapClient->__getLastRequest() : null,
                'requestHeaders' => $dumpRequestHeaders ? $soapClient->__getLastRequestHeaders() : null,
                'responseBody' => $dumpResponseBody ? $soapClient->__getLastResponse() : null,
                'responseHeaders' => $dumpResponseHeaders ? $soapClient->__getLastResponseHeaders() : null,
            ])->filter()->all();
            dump($debugSoapOutput);
        }
    }

    /**
     * Method to store the token to use on subsequent requests.
     *
     * @uses WsdlClientBase::getResult()
     * @uses WsdlClientBase::getSoapClient()
     * @uses WsdlClientBase::saveLastError()
     * @uses WsdlClientBase::setResult()
     */
    final public function setToken(string $soapToken): self
    {
        self::$siiToken = $soapToken;

        try {
            $this->getSoapClient()->__setCookie('TOKEN', $soapToken);
            $this->getAsyncSoapClient()->__setCookie('TOKEN', $soapToken);

            return $this;
        } catch (\SoapFault $soapFault) {
            $this->saveLastError(__METHOD__, $soapFault);

            return $this;
        }
    }

    /**
     * Returns the result.
     *
     * @see AbstractSoapClientBase::getResult()
     *
     * @return null|string
     */
    public function getResult()
    {
        return parent::getResult();
    }
}
