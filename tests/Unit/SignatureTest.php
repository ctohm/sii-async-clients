<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Unit;

use CTOhm\SiiAsyncClients\RequestClients\SoapProvider;
use CTOhm\SiiAsyncClients\Util\ExceptionHelper;

use Tests\Helpers\SiiSignature;

/**
 * A basic test example.
 */
it(
    'Generates a valid Token Request ',
    function (): void {


        $siiSignature = app(SiiSignature::class);
        /** @var \CTOhm\SiiAsyncClients\RequestClients\SoapProvider */
        $this->soapClient = $this->soapClient ?? app()->makeWith(SoapProvider::class, ['siiSignature' => $siiSignature]);

        $tokenRequest = $this->soapClient->getSignedTokenRequest();
        $verificationResult = $siiSignature->verifyXMLVerbose($tokenRequest);
        expect($verificationResult)->toBeArray()
            ->toHaveKey('valid_public_key')
            ->toHaveKey('matching_digests');

        //dd($tokenDoc);
    }
);
