<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use Carbon\Carbon;
use Illuminate\Support\Str;
use JsonSerializable;

/**
 * Parameters (kinda struct type) that are used when performing an operation towards the SII
 * affecting a DTE.
 *
 * @property string $dvEmisor
 * @property string $dvReceptor
 * @property string $dvToken
 * @property string $folio
 * @property int $montoTotal
 * @property string $rutEmisor
 * @property string $rutReceptor
 * @property string $rutToken
 * @property string $siiToken
 * @property string $tipoDoc
 * @property string $transaction_id
 */
class EstadoDteParameters extends EventosHistoricosParameters implements JsonSerializable
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
     * @param array{rutEmisor:string,tipoDoc:int,folio:int,siiToken:string,montoTotal:int} $requestParams
     */
    public function __construct(array $requestParams)
    {
        parent::__construct($requestParams);

        $rutReceptor = (string) ($requestParams['rutReceptor'] ?? '0-0');
        $dvReceptor = $requestParams['dvReceptor'] ?? '';

        if (Str::contains($rutReceptor, '-')) {
            [$rutReceptor, $dvReceptor] = \explode('-', $rutReceptor);
        }
        $this->dvReceptor = $dvReceptor;
        $this->rutReceptor = $rutReceptor;

        $this->siiToken = $requestParams['siiToken'];
        $this->montoTotal = (int) $requestParams['montoTotal'];
    }

    public function jsonSerialize()
    {
        //[$, $m, $d] = \explode('-', $this->fechaEmision);
        $fechaEmision = Carbon::parse($this->fechaEmision);

        return [
            'RutConsultante' => $this->rutToken,
            'DvConsultante' => $this->dvToken,
            'RutCompania' => $this->rutEmisor,
            'DvCompania' => $this->dvEmisor,
            'RutReceptor' => $this->rutReceptor,
            'DvReceptor' => $this->dvReceptor,

            'TipoDte' => $this->tipoDoc,
            'FolioDte' => $this->folio,
            'FechaEmisionDte' => $fechaEmision->format('dmY'), // $d . $m . $Y,
            'MontoDte' => $this->montoTotal,
            'Token' => $this->siiToken,
        ];
    }
}
