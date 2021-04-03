<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

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
    ->tap(static fn ($test) => $test->markTestSkipped('is_true'));
