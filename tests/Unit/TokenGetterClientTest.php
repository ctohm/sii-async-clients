<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Unit;

use CTOhm\SiiAsyncClients\RequestClients\SoapProvider;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EstadoCesionParameters;
use CTOhm\SiiAsyncClients\RequestClients\Structures\EstadoCesionRelacionParameters;
use DOMDocument;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\SiiSignature;

beforeEach(function (): void {
    $this->storage = $this->storage ?? Storage::disk('testing');
    /** @var \CTOhm\SiiAsyncClients\RequestClients\SoapProvider $soapClient */
    $this->soapClient = $this->soapClient ?? app()->makeWith(SoapProvider::class, ['siiSignature' => app(SiiSignature::class)]);

    $this->siiToken = $this->siiToken ?? $this->soapClient->getToken();
});
it('Can retrieve getTokenRequest for a given DTE', function (): void {

    $soapClient = $this->soapClient;


    $getTokenRequest = $soapClient->getTokenRequest();

    expect($getTokenRequest)->toBeInstanceOf(DOMDocument::class);
});

/**
 * A basic test example.
 */
it('Can retrieve getSignedTokenRequest for a given DTE', function (): void {

    $soapClient = $this->soapClient;


    $signedTokenRequest = $soapClient->getSignedTokenRequest();
    expect($signedTokenRequest)->toContain('<getToken>');
});
