<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Unit;

use CTOhm\SiiAsyncClients\RequestClients\RestClient;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EventoHistoricoInstance;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EventosHistoricosParameters;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\SiiSignature;

beforeEach(function (): void {
    $this->storage = $this->storage ?? Storage::disk('testing');
    /** @var \CTOhm\SiiAsyncClients\RequestClients\RestClient $rpetcClient */
    $this->restClient = app()->makeWith(RestClient::class, ['siiSignature' => app(SiiSignature::class)]);
});
it(
    'Can retrieve testRestAuth for a given DTE',
    function ($dte_id): void {
        [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

        $params = [
            'rutEmisor' => $rutEmisor,
            'tipoDoc' => (int) $tipoDoc,
            'folio' => (int) $folio,
        ];
        $requestPayload = new EventosHistoricosParameters($params);

        $dteCedibleResponse = $this->restClient->consultarDocDteCedible($requestPayload/*, ['stats' => true]*/);

        $dteInfo = $dteCedibleResponse->jsonSerialize();

        if (!$this->storage->exists(\sprintf('dteInfo_%s.json', $dte_id))) {
            $this->storage->put(\sprintf('dteInfo_%s.json', $dte_id), \json_encode($dteInfo, \JSON_PRETTY_PRINT));
        }
        expect($dteInfo)
            ->toHaveKey('rutEmisor')
            ->toHaveKey('dvEmisor')
            ->toHaveKey('tipoDoc')
            ->toHaveKey('folio')
            ->toHaveKey('rutReceptor')
            ->toHaveKey('dvReceptor')
            ->toHaveKey('fechaEmision')
            ->toHaveKey('montoTotal');
    }
)->with(['76986660-4_34_95', '76986660-4_34_106', '76986660-4_34_115', '76986660-4_34_135', '76986660-4_34_136']);
/**
 * A basic test example.
 */
it(
    'Can retrieve RpetcClient::listarEventosHistDoc for a given DTE',
    function ($dte_id): void {
        [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

        $requestPayload = new EventosHistoricosParameters([
            'rutEmisor' => $rutEmisor,
            'tipoDoc' => (int) $tipoDoc,
            'folio' => (int) $folio,
        ]);
        $promise = $this->restClient->listarEventosHistDoc($requestPayload)->then(function ($result): void {
            if (\array_key_exists('listEvenHistDoc', $result)) {
                foreach ($result['listEvenHistDoc'] as $evento) {
                    // kdump($evento->jsonSerialize());
                    $this->assertInstanceOf(EventoHistoricoInstance::class, $evento);
                }
            }
        });

        expect($promise)->toBeInstanceOf(PromiseInterface::class);
        $promise->wait();
    }
)->with(['76986660-4_34_95']);
