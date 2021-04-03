<?php

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
 * @property string $dvReceptor
 * @property string $dvToken
 * @property int $folio
 * @property int $montoTotal
 * @property string $rutEmisor
 * @property string $rutReceptor
 * @property string $rutToken
 * @property string $siiToken
 * @property int $tipoDoc
 * @property string $transaction_id
 */
class EstadoCesionParameters extends EventosHistoricosParameters implements Arrayable, JsonSerializable
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

    public string $rutReceptor;

    public int $montoTotal;

    public string $dvReceptor;

    public ?string $siiToken = null;

    /**
     * Undocumented function.
     *
     * @param array{rutEmisor:string,dvEmisor:mixed,rutReceptor:mixed,dvReceptor:mixed,tipoDoc:mixed,folio:mixed,siiToken:string,montoTotal:int,fechaEmision:string} $requestParams
     */
    public function __construct(array $requestParams)
    {
        $requestParams['tipoDoc'] = (int) $requestParams['tipoDoc'];
        parent::__construct($requestParams);

        $rutReceptor = $requestParams['rutReceptor'];
        $dvReceptor = $requestParams['dvReceptor'] ?? null;

        if (Str::contains($rutReceptor, '-')) {
            [$rutReceptor, $dvReceptor] = \explode('-', $rutReceptor);
        }
        $this->dvReceptor = $dvReceptor;
        $this->rutReceptor = $rutReceptor;
        $this->siiToken = $requestParams['siiToken'];
        $this->montoTotal = $requestParams['montoTotal'];
    }

    /**
     * Returns every stored DTE attribute by merging _data and listEvenHistDoc.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return (int|string)[]
     *
     * @psalm-return array{Token: string, RutEmisor: string, DVEmisor: string, TipoDoc: int, FolioDoc: int}
     */
    public function toArray(): array
    {
        //[$, $m, $d] = \explode('-', $this->fechaEmision);
        return [
            'Token' => $this->siiToken,
            'RutEmisor' => $this->rutEmisor,
            'DVEmisor' => $this->dvEmisor,
            'TipoDoc' => $this->tipoDoc,
            'FolioDoc' => $this->folio,
        ];
    }
}
