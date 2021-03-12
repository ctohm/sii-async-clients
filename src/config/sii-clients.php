<?php

/**
 * CTOhm - SII Async Clients
 */

return [
    'cache_folder' => storage_path(env('SII_CACHE_FOLDER', 'wsdl/cache')),
    'cache_policy' => \constant(env('SII_CACHE_POLICY', 'WSDL_CACHE_NONE')),
    'default_request_delay_ms' => (int) (env('SII_REQUEST_DELAY_MS', 100)),
    'soap_debug' => env('SOAP_DEBUG', false),
    'transaction_id' => \md5(\random_bytes(32)),
    'cacert_pemfile' => __DIR__ . '/../resources/cacert.pem',
];
