<?php

/**
 * CTOhm - SII Async Clients
 */

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(Tests\BaseTestCase::class)->in('Unit');
uses(Tests\BaseTestCase::class)->in('Feature');

function assertIsTest()
{

    /** @var  \Tests\BaseTestCase $test */
    $test = test();
    //kdump($test->getName(true), $test->getTestResultObject());
    //dump($test->getProvidedData());
    $test->assertEmpty([]); // return get_object_vars();
    return $test;
}
