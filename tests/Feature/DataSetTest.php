<?php

/**
 * CTOhm - SII Async Clients
 */

it('is_empty')->assertEmpty([]);
it(
    '<fg=blue>Test 1</> runs with given element:',
    function (
        string $email,
        string $loaded_at
    ): void {
        $this->writeWhenRun('Test 1', $email, $loaded_at, \microtime(true), '#090', '#AA1');

        expect($email)->toBeString();
    }
)->with('files')->depends('is_empty');

it(
    '<fg=magenta>Test 2</> runs with given element:',
    function (
        string $email,
        string $loaded_at
    ): void {
        $this->writeWhenRun('Test 2', $email, $loaded_at, \microtime(true), '#3A3', '#CC4');

        expect($email)->toBeString();
    }
)->with('files')->dependsEach('<fg=blue>Test 1</> runs with given element:');
