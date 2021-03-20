<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;

/**
 * Parameters (kinda struct type) that are used when performing an operation towards the SII
 * affecting a DTE.
 *
 * @property string $dvEmisor
 * @property string $dvToken
 * @property string $folio
 * @property string $rutEmisor
 * @property string $rutToken
 * @property string $tipoDoc
 * @property string $transaction_id
 */
class EventosHistoricosParameters implements Arrayable, JsonSerializable
{
    public string $transaction_id;

    public ?string $rutToken;

    public string $dvToken;

    public string $rutEmisor;

    public string $dvEmisor;

    public ?int $tipoDoc;

    public ?int $folio;

    public string $dte_id;

    public $fechaEmision;

    /**
     * Undocumented function.
     *
     * @param array{rutEmisor:string|null,dvEmisor:mixed|null,tipoDoc:mixed,folio:mixed,fechaEmision:mixed|null} $requestParams
     */
    public function __construct(array $requestParams)
    {
        $rutEmisor = (string) ($requestParams['rutEmisor'] ?? '0-0');
        $dvEmisor = $requestParams['dvEmisor'] ?? '';

        if (Str::contains($rutEmisor, '-')) {
            [$rutEmisor, $dvEmisor] = \explode('-', $rutEmisor);
        }
        $this->dvEmisor = $dvEmisor;
        $this->rutEmisor = $rutEmisor;

        [$rutToken, $dvToken] = \explode('-', config('sii.rut_certificado', '0-0'));
        $this->transaction_id = config('sii-clients.transaction_id', '1234');

        $this->tipoDoc = (int) $requestParams['tipoDoc'];
        $this->folio = (int) $requestParams['folio'];

        $this->dte_id = \sprintf('%s-%s_%d_%d', $this->rutEmisor, $this->dvEmisor, $this->tipoDoc ?? 33, $this->folio ?? 1);
        $this->rutToken = $rutToken;
        $this->dvToken = $dvToken;

        $this->fechaEmision = $requestParams['fechaEmision'] ?? '';
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return string[]
     *
     * @psalm-return array{dte_id: string, rutEmisor: string, fechaEmision: string, tipoDoc: string, folio: string}
     */
    public function toArray(): array
    {
        return [
            'dte_id' => $this->dte_id,

            'rutEmisor' => \sprintf('%d-%s', $this->rutEmisor, $this->dvEmisor),
            'fechaEmision' => $this->fechaEmision,
            'tipoDoc' => $this->tipoDoc,
            'folio' => $this->folio,
        ];
    }
}
