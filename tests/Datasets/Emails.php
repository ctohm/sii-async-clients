<?php

/**
 * CTOhm - SII Async Clients
 */

use Illuminate\Support\Facades\Storage;

$loader = static function ($filename) {
    return [$filename, Storage::exists($filename), false];
};
$files = [
    'file/11111.xml',
    'file/22222.xml',
    'file/33333.xml',
    'file/44444.xml',
    'file/55555.xml',
    'file/66666.xml',
];
dataset(
    'files',
    get_files($files)
); /*
dataset(
    'files_content',
    get_files($files)
);*/
