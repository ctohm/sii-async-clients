<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Unit;

use CTOhm\SiiAsyncClients\RequestClients\SoapProvider;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EstadoCesionParameters;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EstadoCesionRelacionParameters;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\SiiSignature;

beforeEach(function (): void {
    $this->storage = $this->storage ?? Storage::disk('testing');
    /** @var \CTOhm\SiiAsyncClients\RequestClients\SoapProvider $soapClient */
    $this->soapClient = $this->soapClient ?? app()->makeWith(SoapProvider::class, ['siiSignature' => app(SiiSignature::class)]);

    $this->siiToken = $this->siiToken ?? $this->soapClient->getToken();
});
/**
 * A basic test example.
 */
it(
    'Can retrieve testGetEstadoCesion for a given DTE',
    function ($dte_id): void {
        $dteInfo = \json_decode($this->storage->get(\sprintf('dteInfo_%s.json', $dte_id)), true);

        $siiToken = $this->siiToken;
        $soapClient = $this->soapClient;

        $dteInfoWithToken = \array_merge($dteInfo, ['siiToken' => $siiToken]);

        $estCesionArgs = new EstadoCesionParameters($dteInfoWithToken);
        $estCesionResult = $soapClient->getEstadoCesion($estCesionArgs)->then(function ($result): void {
            //  $this->assertArrayHasKey("estado", $result);
            $this->assertArrayHasKey('glosa', $result);
            $this->assertArrayHasKey('resp_body', $result);
        });

        $this->assertInstanceOf(PromiseInterface::class, $estCesionResult);
        $estCesionResult->wait();
    }
)->with(['76986660-4_34_95', '76986660-4_34_106', '76986660-4_34_115']);

//getEstCesion
//getEstCesionRelac
//getEstEnvio
/**
 * A basic test example.
 */
it(
    'Can retrieve getEstadoCesionRelacion for a given DTE',
    function ($dte_id): void {
        [$rutEmisor, $tipoDoc, $folio] = \explode('_', (string) ($dte_id ?? '1-1_0_0'));

        $dteInfo = \json_decode($this->storage->get(\sprintf('dteInfo_%s.json', $dte_id)), true);
        $siiToken = $this->siiToken;
        $soapClient = $this->soapClient;

        $array_cesion_relacion = \array_merge($dteInfo, ['siiToken' => $siiToken, 'rutEmpresa' => $rutEmisor]);
        $estCesionRelacionArgs = new EstadoCesionRelacionParameters($array_cesion_relacion);

        $promise = $soapClient->getEstadoCesionRelacion($estCesionRelacionArgs)->then(function ($result): void {
            $this->assertArrayHasKey('estado', $result);
            $this->assertArrayHasKey('rut_tenedor', $result);
            $this->assertArrayHasKey('fecha_ult_anot', $result);
        });

        expect($promise)->toBeInstanceOf(PromiseInterface::class);
        $promise->wait();
    }
)->with(['76986660-4_34_95', ['76986660-4_34_106', true]]);
it(
    'Can retrieve AEC getEstadoEnvio from track_id',
    function ($track_id): void {
        /** @var \CTOhm\SiiAsyncClients\RequestClients\SoapProvider $soapClient */
        $soapClient = $this->soapClient;
        $siiToken = $this->siiToken;

        $promise = $soapClient->getEstEnvio($siiToken, $track_id)->then(function ($result) use ($track_id): void {
            $this->assertArrayHasKey('estado', $result);
            $this->assertArrayHasKey('trackid', $result);
            $this->assertArrayHasKey('desc_estado', $result);
            $this->assertArrayHasKey('estado_envio', $result);
            expect($result['trackid'])->toEqual($track_id);
        });

        expect($promise)->toBeInstanceOf(PromiseInterface::class);
        $promise->wait();
    }
)->with(['5131325004']);
