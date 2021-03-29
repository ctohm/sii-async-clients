<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Feature;

use CTOhm\SiiAsyncClients\RequestClients\SoapProvider;
use CTOhm\SiiAsyncClients\Util\ExceptionHelper;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\SiiSignature;

test('is_true')->with('lambdas')->assertIsTest([]);
/**
 * A basic test example.
 */
it(
    'depends on another test',
    function (): TestCase {
        expect($this)->toBeInstanceOf(TestCase::class);

        return $this;
    }
)->with('lambdas')->markTestSkipped('is_true')
    ->tap(fn ($test) =>  $test->markTestSkipped('is_true'));
