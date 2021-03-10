<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\Wsdl\AsyncSoap;

use CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding\SoapHttpBinding;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @method void __setCookie($name, $value)
 * @method object getEstDte( $rutConsultante,$dvConsultante,$rutCompania,$dvCompania,$rutReceptor,$dvReceptor,$tipoDte,$folioDte,$fechaEmisionDte,$montoDte,$token)
 *
 * @internal
 * @psalm-internal CTOhm\SiiAsyncClients\Wsdl
 */
class AsyncSoapClient extends Guzzle\SoapClient implements SoapClientInterface
{
    private PromiseInterface $httpBindingPromise;

    private ClientInterface $client;

    public function __construct(ClientInterface $client, PromiseInterface $httpBindingPromise)
    {
        $this->httpBindingPromise = $httpBindingPromise;
        $this->client = $client;
    }

    public function __call(
        $name,
        $arguments
    ) {
        return $this->callAsync($name, $arguments);
    }

    public function getInternalClient(): PromiseInterface
    {
        return $this->httpBindingPromise
            ->then(
                static function (SoapHttpBinding $binding) {
                    $interpreter = $binding
                        ->getInterpreter();

                    return $interpreter->getSoapClient();
                }
            );
    }

    public function call(
        $name,
        array $arguments,
        ?array $options = null,
        $inputHeaders = null,
        ?array &$outputHeaders = null
    ) {
        $callPromise = $this->callAsync($name, $arguments, $options, $inputHeaders, $outputHeaders);

        return $callPromise->wait();
    }

    public function callAsync(
        $name,
        array $arguments,
        ?array $options = null,
        $inputHeaders = null,
        ?array &$outputHeaders = null
    ) {
        return \GuzzleHttp\Promise\coroutine(
            function () use ($name, $arguments, $options, $inputHeaders, &$outputHeaders) {
                /** @var \CTOhm\SiiAsyncClients\Wsdl\AsyncSoap\HttpBinding\HttpBinding $httpBinding */
                $httpBinding = (yield $this->httpBindingPromise);
                $request = $httpBinding->request($name, $arguments, $options, $inputHeaders);
                $requestOptions = $options['request_options'] ?? [];

                try {
                    $response = (yield $this->client->sendAsync($request, $requestOptions));

                    yield $this->interpretResponse($httpBinding, $response, $name, $outputHeaders);
                } catch (RequestException $exception) {
                    if ($exception->hasResponse()) {
                        $response = $exception->getResponse();

                        yield $this->interpretResponse($httpBinding, $response, $name, $outputHeaders);
                    } else {
                        throw $exception;
                    }
                } finally {
                    $request->getBody()->close();
                }
            }
        );
    }

    private function interpretResponse(SoapHttpBinding $httpBinding, ResponseInterface $response, string $name, &$outputHeaders)
    {
        try {
            return $httpBinding->response($response, $name, $outputHeaders);
        } finally {
            $response->getBody()->close();
        }
    }
}
