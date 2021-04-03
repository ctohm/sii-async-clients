<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use Illuminate\Support\Str;
use JsonSerializable;

/**
 * Parameters (kinda struct type) that are used when performing an operation towards the SII
 * affecting a DTE.
 *
 * @property string $dvEmisor
 * @property string $dvEmpresa
 * @property string $folio
 * @property string $rutEmisor
 * @property string $rutEmpresa
 * @property string $siiToken
 * @property string $tipoDoc
 * @property string $transaction_id
 */
class EstadoCesionRelacionParameters extends EventosHistoricosParameters implements JsonSerializable
{
    public string $rutEmisor;

    public string $dvEmisor;

    public ?int $tipoDoc;

    public ?int $folio;

    public string $dte_id;

    public ?string $siiToken = null;

    public ?string $rutEmpresa = null;

    public ?string $dVEmpresa = null;

    /**
     * Undocumented function.
     *
     * @param array{rutEmisor:string,tipoDoc:int,folio:int,siiToken:string,rutEmpresa:string,?dvEmpresa:string,?siiToken:string} $requestParams
     */
    public function __construct(array $requestParams)
    {
        parent::__construct($requestParams);

        $rutEmpresa = (string) ($requestParams['rutEmpresa'] ?? '0-0');
        $dvEmpresa = $requestParams['dvEmpresa'] ?? '';

        if (Str::contains($rutEmpresa, '-')) {
            [$rutEmpresa, $dvEmpresa] = \explode('-', $rutEmpresa);
            $requestParams['rutEmpresa'] = $rutEmpresa;
            $requestParams['dvEmpresa'] = $dvEmpresa;
        }
        $this->siiToken = $requestParams['siiToken'];
        $this->rutEmpresa = $rutEmpresa ?? config('sii.rut_financia');
        /**
         * @psalm-suppress UndefinedThisPropertyAssignment
         */
        $this->dvEmpresa = $requestParams['dvEmpresa'] ?? config('sii.dv_financia');
    }

    public function jsonSerialize()
    {
        //[$, $m, $d] = \explode('-', $this->fechaEmision);
        return [
            'Token' => $this->siiToken,
            'RutEmisor' => $this->rutEmisor,
            'DVEmisor' => $this->dvEmisor,
            'TipoDoc' => $this->tipoDoc,
            'FolioDoc' => $this->folio,
            'RutEmpresa' => $this->rutEmpresa,
            'DvEmpresa' => $this->dvEmpresa,
        ];
    }
}
