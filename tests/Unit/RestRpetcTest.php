<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Unit;

use Carbon\Carbon;
use CTOhm\SiiAsyncClients\RequestClients\RpetcClient;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EventosHistoricosParameters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\SiiSignature;

beforeEach(function (): void {
    $this->storage = $this->storage ?? Storage::disk('testing');
    /** @var \CTOhm\SiiAsyncClients\RequestClients\RestClient $rpetcClient */
    $this->restClient = app()->makeWith(RpetcClient::class, ['siiSignature' => app(SiiSignature::class)]);
});

it(
    'Can retrieve received Cesiones form to create certificates',
    function ($dte_id): void {
        [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

        $fecha_desde = Carbon::parse('2020-03-01');
        $fecha_hasta = Carbon::parse('2020-05-21');
        $result = $this->restClient->getCertificadosForm($rutEmisor, $fecha_desde, $fecha_hasta, ['TIPOCONSULTA' => RpetcClient::TIPO_CEDENTE]);

        expect($result)->toBeInstanceOf(Collection::class);
        // expect($result->get('cesiones'))->toBeInstanceOf(Collection::class);
        // $result->get('cesiones')->each(fn (array $item) => expect($item)->toHaveKeys(['estado_cesion', 'deudor', 'mnt_cesion', 'cedente', 'cesionario']));
        //
    }
)
    ->group('getAec')
    ->with(['76986660-4_34_136']);

it(
    'Can retrieve received Cesiones as XML',
    function ($dte_id): void {
        [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

        $fecha_desde = Carbon::parse('2020-12-12');
        $fecha_hasta = Carbon::parse('2020-12-21');
        $result = $this->restClient->getCesionesRecibidas($rutEmisor, $fecha_desde, $fecha_hasta, ['TXTXML' => 'XML']);

        expect($result)->toBeInstanceOf(Collection::class);
        expect($result->get('cesiones'))->toBeInstanceOf(Collection::class);
        $result->get('cesiones')->each(static fn (array $item) => expect($item)->toHaveKeys(['estado_cesion', 'deudor', 'mnt_cesion', 'cedente', 'cesionario']));
    }
)
    ->group('getAec')
    ->with(['76986660-4_34_136']);

it(
    'Can retrieve AEC file',
    function ($dte_id): void {
        [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

        $eventosHistoricosParameters = new EventosHistoricosParameters([
            'rutEmisor' => $rutEmisor,
            'tipoDoc' => $tipoDoc,
            'folio' => $folio,
        ]);

        $result = $this->restClient->getAecFile($rutEmisor, $eventosHistoricosParameters);

        expect($result)->toBeString();
        expect($result)->toContain('<AEC');
        //dump($result);
    }
)
    ->group('getAec')
    ->with(['76986660-4_34_136']);
it(
    'Can retrieve AEC details',
    function ($dte_id): void {
        [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

        $infoDetalle = $this->restClient->getDetalleCesionRTC($rutEmisor, $dte_id);

        // dump($infoDetalle);
        expect($infoDetalle)->toBeArray();
        $this->assertArrayHasKey('tenedor_vigente', $infoDetalle);
        $this->assertArrayHasKey('fecha_ultima_anotacion', $infoDetalle);
        $this->assertArrayHasKey('declaracion_jurada', $infoDetalle);
        $this->assertArrayHasKey('clave_de_acceso', $infoDetalle);
    }
)
    ->group('getAecDetalle')
    ->with(['76986660-4_34_136']);
it(
    'Can retrieve received Cesiones as CSV',
    function ($dte_id): void {
        [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

        $fecha_desde = Carbon::parse('2020-12-12');
        $fecha_hasta = Carbon::parse('2020-12-21');
        $result = $this->restClient->getCesionesRecibidas($rutEmisor, $fecha_desde, $fecha_hasta);
        expect($result)->toBeInstanceOf(Collection::class);
        $result->each(static fn ($res) => expect($res)->toHaveKeys(['estado_cesion', 'deudor', 'mnt_cesion', 'cedente', 'cesionario']));

        //dump($result);
    }
)
    ->group('getAec')
    ->with(['76986660-4_34_136']);
