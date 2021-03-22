<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @property string $cert
 * @property string $pkey
 * @property array $extracerts
 */
interface CertificatesObjectInterface extends Arrayable, JsonSerializable
{
    public function jsonSerialize(): array;

    public function toArray(): array;


    public  function getCertFiles(): array;
}
