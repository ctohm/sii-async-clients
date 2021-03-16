<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Unit;

use CTOhm\SiiAsyncClients\RequestClients\RestClient;
use CTOhm\SiiAsyncClients\RequestClients\RpetcClient;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EventoHistoricoInstance;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EventosHistoricosParameters;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\SiiSignature;

beforeEach(function (): void {
    $this->storage = $this->storage ?? Storage::disk('testing');
    /** @var \CTOhm\SiiAsyncClients\RequestClients\RestClient $rpetcClient */
    $this->restClient = app()->makeWith(RpetcClient::class, ['siiSignature' => app(SiiSignature::class)]);
});

it('Can retrieve AEC file', function ($dte_id): void {
    [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

    $eventosHistoricosParameters = new EventosHistoricosParameters([
        'rutEmisor' => $rutEmisor,
        'tipoDoc' => $tipoDoc,
        'folio' => $folio
    ]);

    $result = $this->restClient->getAecFile($rutEmisor, $eventosHistoricosParameters);

    expect($result)->toBeString();
    expect($result)->toContain('<AEC');
    //dump($result);
})
    ->group('getAec')
    ->with(['76986660-4_34_136']);
it('Can retrieve AEC details', function ($dte_id): void {
    [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));


    $infoDetalle = $this->restClient->getDetalleCesionRTC($rutEmisor, $dte_id);

    // dump($infoDetalle);
    expect($infoDetalle)->toBeArray();
    $this->assertArrayHasKey('tenedor_vigente', $infoDetalle);
    $this->assertArrayHasKey('fecha_ultima_anotacion', $infoDetalle);
    $this->assertArrayHasKey('declaracion_jurada', $infoDetalle);
    $this->assertArrayHasKey('clave_de_acceso', $infoDetalle);
})
    ->group('getAecDetalle')
    ->with(['76986660-4_34_136']);
