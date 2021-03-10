<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * App\Document.
 *
 * @property int $id
 * @property string $dte_id
 * @property int $folio
 * @property string $rut_emisor
 * @property null|string $tipo_dte
 * @property \Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property null|\Illuminate\Support\Carbon $fecha_emision
 * @property null|int $cession_events_count
 * @property null|int $eventos_historicos_count
 */
interface RetrievesEventosHistoricosInterface
{
    public function listarEventosHistDoc(EventosHistoricosParameters $requestPayload): ?PromiseInterface;
}
