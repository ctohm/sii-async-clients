<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Unit;

use CTOhm\SiiAsyncClients\RequestClients\SoapProvider;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EstadoDteAvParameters;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EstadoDteParameters;
use CTOhm\SiiAsyncClients\Wsdl\SoapClients\WsdlClientBase;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Helpers\SiiSignature;

beforeEach(function (): void {
    $this->storage = $this->storage ?? Storage::disk('testing');
    /** @var \CTOhm\SiiAsyncClients\RequestClients\SoapProvider */
    $this->soapClient = $this->soapClient ?? app()->makeWith(SoapProvider::class, ['siiSignature' => app(SiiSignature::class)]);

    $this->siiToken = $this->siiToken ?? $this->soapClient->getToken();
});
/**
 * A basic test example.
 */
it(
    'Can retrieve testGetEstDte for a given DTE',
    function ($dte_id): void {
        $dteInfo = \json_decode($this->storage->get(\sprintf('dteInfo_%s.json', $dte_id)), true);

        $siiToken = $this->siiToken;
        /** @var \CTOhm\SiiAsyncClients\RequestClients\SoapProvider $soapClient */
        $soapClient = $this->soapClient;

        $dteInfoWithToken = \array_merge($dteInfo, ['siiToken' => $siiToken]);
        $estDteArgs = new EstadoDteParameters($dteInfoWithToken);
        $testGetEstDte = $soapClient->getEstadoDte($estDteArgs)->then(function ($estDteResult): void {
            expect($estDteResult)

                ->toHaveKey('estado')
                ->toHaveKey('glosaEstado')
                ->toHaveKey('errCode')
                ->toHaveKey('glosaErr')
                ->toHaveKey('numAtencion');
        });


        $this->assertInstanceOf(PromiseInterface::class, $testGetEstDte);
        $testGetEstDte->wait();
    }
)->with(['76986660-4_34_135', '76986660-4_34_136']);

/**
 * A basic test example.
 */
it(
    'Can retrieve testGetEstDteAv for a given DTE',
    function ($dte_id): void {
        $dteInfo = \json_decode($this->storage->get(\sprintf('dteInfo_%s.json', $dte_id)));

        $siiToken = $this->siiToken;
        /** @var \CTOhm\SiiAsyncClients\RequestClients\SoapProvider $soapClient */
        $soapClient = $this->soapClient;
        $xml_string = $this->storage->get(\sprintf('DTE_%s.xml', $dte_id));
        $SignatureValue = Str::of($xml_string)->between('<SignatureValue>', '</SignatureValue>')
            ->replace("\r\n", "\n")->replace("\n", '')
            ->__toString();

        $estDteAvArgs = [
            'rutEmisor' => $dteInfo->rutEmisor,
            'tipoDoc' => $dteInfo->tipoDoc,
            'folio' => $dteInfo->folio,
            'rutReceptor' => $dteInfo->rutReceptor,
            'montoTotal' => $dteInfo->montoTotal,
            'fechaEmision' => $dteInfo->fechaEmision,
            'firmaDte' => $SignatureValue,
            'siiToken' => $siiToken,
        ];

        $estDteArgs = new EstadoDteAvParameters($estDteAvArgs);
        $testGetEstDte = $soapClient->getEstadoDteAv($estDteArgs)->then(function ($estDteResult): void {
            expect($estDteResult)

                ->toHaveKey('estado')
                ->toHaveKey('recibido')
                ->toHaveKey('glosa')
                ->toHaveKey('trackid')
                ->toHaveKey('numatencion');
        });

        $this->assertInstanceOf(PromiseInterface::class, $testGetEstDte);
        $testGetEstDte->wait();
    }
)->with(['76986660-4_34_135', '76986660-4_34_136']);
