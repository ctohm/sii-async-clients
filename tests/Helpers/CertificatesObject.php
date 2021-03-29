<?php

declare(strict_types=1);

/**
 * DBThor Cesion 1.11.0
 */

namespace Tests\Helpers;

use CTOhm\SiiAsyncClients\RequestClients\Structures\CertificatesObjectInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @property string $cert
 * @property string $pkey
 * @property array $extracerts
 */
class CertificatesObject implements CertificatesObjectInterface, JsonSerializable, Arrayable
{
    public string $pkey;
    public string $cert;
    public ?array $extracerts;


    /**
     * Resource pointing to a temporary file
     *
     * @var resource
     */
    private $certFile;
    /**
     * Resource pointing to a temporary file
     *
     * @var resource
     */
    private $pkeyFile;
    /**
     * Resource pointing to a temporary file
     *
     * @var resource
     */
    private $caFile;

    /**
     * Undocumented function.
     *
     * @param array{pkey:string|null,cert:mixed|null,extracerts:mixed|null} $certs
     */
    public function __construct(array $certs)
    {
        $this->pkey = $certs['pkey'];
        $this->cert = $certs['cert'];
        $this->extracerts = $certs['extracerts'] ?? null;


        $this->certFile = \tmpfile();
        $this->pkeyFile =  \tmpfile();

        \fwrite($this->pkeyFile, $certs['pkey']);
        \fwrite($this->certFile, $certs['cert']);

        if ($this->extracerts) {
            $this->caFile =  \tmpfile();
            foreach ($this->extracerts as $extracert) {
                \fwrite($this->caFile, $extracert);
            }
        }
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return  [
            'pkey' => $this->pkey,
            'cert' => $this->cert,
            'extracerts' => $this->extracerts
        ];
    }
    private array $certpaths = [];


    /**
     * Gets the cert files.
     *
     * @return array{cert:string,ssl_key:string,verify:string|null} array of paths to the cert files
     */
    public function getPaths(): array
    {
        if (count($this->certpaths) === 0) {
            $this->certpaths = [
                'cert' => \stream_get_meta_data($this->certFile)['uri'],
                'ssl_key' => \stream_get_meta_data($this->pkeyFile)['uri'],
                'verify' => $this->caFile ? \stream_get_meta_data($this->caFile)['uri'] : config('sii-clients.cacert_pemfile')
            ];
        }

        return $this->certpaths;
    }
}
