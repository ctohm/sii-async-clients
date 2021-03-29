<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

$loader = function ($filename) {

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
);
dataset(
    'emails',
    get_emails()
);
