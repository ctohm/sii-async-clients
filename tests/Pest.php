<?php

/**
 * CTOhm - SII Async Clients
 */

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(Tests\BaseTestCase::class)->in('Unit');
uses(Tests\BaseTestCase::class)->in('Feature');
function get_lambdas()
{
    return [
        'lambda1', 'lambda2'
    ];
}
function assertIsTest()
{

    /** @var  \Tests\BaseTestCase $test */
    $test = test();
    //kdump($test->getName(true), $test->getTestResultObject());
    //dump($test->getProvidedData());
    $test->assertEmpty([]); // return get_object_vars();
    return $test;
}

function get_emails()
{
    return function () {



        yield [Str::lower('<fg=#F66>user_11111@mail.com</>'), microtime(true)];



        yield [Str::lower('<fg=#CC6>user_22222@mail.com</>'), microtime(true)];



        yield [Str::lower('<fg=#66F>user_33333@mail.com</>'), microtime(true)];
    };
}
