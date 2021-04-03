<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use Carbon\Carbon;
use JsonSerializable;
use Illuminate\Support\Str;

/**
 * Parameters (kinda struct type) that are used when performing an operation towards the SII
 * affecting a DTE.
 *
 * @property string $dvEmisor
 * @property string $dvReceptor
 * @property string $dvToken
 * @property string $firmaDte
 * @property string $folio
 * @property int $montoTotal
 * @property string $rutEmisor
 * @property string $rutReceptor
 * @property string $rutToken
 * @property string $siiToken
 * @property string $tipoDoc
 * @property string $transaction_id
 */
class EstadoDteAvParameters extends EstadoDteParameters implements JsonSerializable
{
    public string $firmaDte;

    /**
     * Undocumented function.
     *
     * @param array{rutEmisor:string,tipoDoc:int,folio:int,siiToken:string,firmaDte:string,montoTotal:int,fechaEmision:string,dvEmisor:string} $requestParams
     */
    public function __construct(array $requestParams)
    {
        $rutEmisor = (string) ($requestParams['rutEmisor'] ?? '0-0');


        if (Str::contains($rutEmisor, '-')) {
            [$rutEmisor, $dvEmisor] = \explode('-', $rutEmisor);
            $requestParams['dvEmisor'] =  $dvEmisor;
            $requestParams['rutEmisor'] = $rutEmisor;
        }

        parent::__construct($requestParams);

        $this->firmaDte = $requestParams['firmaDte'];
    }

    public function jsonSerialize()
    {
        //[$, $m, $d] = \explode('-', $this->fechaEmision);
        $fechaEmision = Carbon::parse($this->fechaEmision);

        return [
            //'RutConsultante' => $this->rutToken,
            //'DvConsultante' => $this->dvToken,
            'RutCompania' => $this->rutEmisor,
            'DvCompania' => $this->dvEmisor,
            'RutReceptor' => $this->rutReceptor,
            'DvReceptor' => $this->dvReceptor,

            'TipoDte' => $this->tipoDoc,
            'FolioDte' => $this->folio,
            'FechaEmisionDte' => $fechaEmision->format('dmY'), // $d . $m . $Y,
            'MontoDte' => $this->montoTotal,
            'firmaDte' => $this->firmaDte,
            'Token' => $this->siiToken,
        ];
    }
}
