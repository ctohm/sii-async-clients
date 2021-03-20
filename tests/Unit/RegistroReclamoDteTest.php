<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Unit;

use CTOhm\SiiAsyncClients\RequestClients\SoapProvider;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EstadoDteParameters;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EventoHistoricoInstance;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EventosHistoricosParameters;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\SiiSignature;

beforeEach(function (): void {
    $this->storage = $this->storage ?? Storage::disk('testing');
    /** @var \CTOhm\SiiAsyncClients\RequestClients\SoapProvider $soapClient */
    $this->soapClient = $this->soapClient ?? app()->makeWith(SoapProvider::class, ['siiSignature' => app(SiiSignature::class)]);

    $this->siiToken = $this->siiToken ?? $this->soapClient->getToken();
});

//ingresarAceptacionReclamoDoc
//onsultarFechaRecepcionSii

/**
 * A basic test example.
 */
it(
    'Can retrieve listarEventosHistDoc for a given DTE',
    function ($dte_id): void {
    [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

    $siiToken = $this->siiToken;
    $soapClient = $this->soapClient;

    $requestPayload = new EventosHistoricosParameters([
        'rutEmisor' => $rutEmisor,
        'tipoDoc' => (int) $tipoDoc,
        'folio' => (int) $folio,
    ]);
    $promise = $soapClient->listarEventosHistDoc($requestPayload)->then(function ($result): void {
        if (\array_key_exists('listEvenHistDoc', $result)) {
            foreach ($result['listEvenHistDoc'] as $evento) {
                //$this->kdump($evento->jsonSerialize());
                $this->assertInstanceOf(EventoHistoricoInstance::class, $evento);
            }
        }
    });

    $this->assertInstanceOf(PromiseInterface::class, $promise);
    $promise->wait();
}
)->with(['76986660-4_34_95']);
/**
 * A basic test example.
 */
it(
    'Can retrieve consultarDocDteCedible for a given DTE',
    function ($dte_id): void {
    [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

    $dteInfo = \json_decode($this->storage->get(\sprintf('dteInfo_%s.json', $dte_id)), true);

    $siiToken = $this->siiToken;
    $soapClient = $this->soapClient;

    $dteInfoWithToken = \array_merge($dteInfo, ['siiToken' => $siiToken]);
    $estDteArgs = new EstadoDteParameters($dteInfoWithToken);
    $promise = $soapClient->consultarDocDteCedible($estDteArgs)->then(function ($result): void {
        $this->assertArrayHasKey('codResp', $result);
        $this->assertArrayHasKey('descResp', $result);
    });

    $this->assertInstanceOf(PromiseInterface::class, $promise);
    $promise->wait();
}
)->with(['76986660-4_34_95', '76986660-4_34_106', '76986660-4_34_115']);
