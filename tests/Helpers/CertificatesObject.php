<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace Tests\Helpers;

use CTOhm\SiiAsyncClients\RequestClients\Structures\CertificatesObjectInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @property string $cert
 * @property array $extracerts
 * @property string $pkey
 */
class CertificatesObject implements Arrayable, CertificatesObjectInterface, JsonSerializable
{
    public ?array $extracerts;

    public string $pkey;

    public string $cert;

    /**
     * Undocumented function.
     *
     * @param array{pkey:string|null,cert:mixed|null,extracerts:mixed|null} $certs
     */
    public function __construct(array $certs)
    {
        $this->pkey = $certs['pkey'];
        $this->cert = $certs['cert'];
        $this->extracerts = $certs['extracerts'] ?? '';
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'pkey' => $this->pkey,
            'cert' => $this->cert,
            'extracerts' => $this->extracerts,
        ];
    }
}
