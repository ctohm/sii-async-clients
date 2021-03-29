<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Unit;

use Carbon\Carbon;
use CTOhm\SiiAsyncClients\RequestClients\DteWsClient;
use CTOhm\SiiAsyncClients\RequestClients\RpetcClient;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EventosHistoricosParameters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\SiiSignature;

beforeEach(function (): void {
    $this->storage = $this->storage ?? Storage::disk('testing');
    /** @var \CTOhm\SiiAsyncClients\RequestClients\DteWsClient $rpetcClient */
    $this->restClient = app()->makeWith(DteWsClient::class, ['siiSignature' => app(SiiSignature::class)]);
});

it(
    'Can retrieve received Cesiones form to create certificates',
    function ($dte_id): void {
        [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

        $fecha_desde = Carbon::parse('2020-03-01')->format('Y-m-d');
        $fecha_hasta = Carbon::parse('2020-05-21')->format('Y-m-d');
        /** @var DteWsClient $result */
        $restClient = $this->restClient;
        $result = $restClient->listaDocumentosEmitidos($rutEmisor, $fecha_desde, $fecha_hasta);

        expect($result)->toBeArray();
        // expect($result->get('cesiones'))->toBeInstanceOf(Collection::class);
        // $result->get('cesiones')->each(fn (array $item) => expect($item)->toHaveKeys(['estado_cesion', 'deudor', 'mnt_cesion', 'cedente', 'cesionario']));
        //
    }
)
    ->group('listaDocumentosEmitidos')
    ->with(['76986660-4_34_136']);
