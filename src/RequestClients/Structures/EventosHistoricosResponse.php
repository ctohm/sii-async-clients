<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use Illuminate\Support\Facades\Log;
use JsonSerializable;

/**
 * Structure for the response that SII returns when requesting Event History for a DTE
 * Web Client and Soap WS return different structures. This class ensures proper normalization
 * and type checking.
 *
 * @property array $_data
 * @property EventoHistoricoInstance[] $listEvenHistDoc
 */
class EventosHistoricosResponse implements JsonSerializable
{
    /**
     * @var EventoHistoricoInstance[]
     *
     * @psalm-var array<array-key, EventoHistoricoInstance>
     */
    public $listEvenHistDoc = [];

    private array $_data = [];

    private array $_discardedFields = [
        'dhdrCodigo',
        'dtdcCodigo',
        'dtdcCodigo',
        'descDoc',
        'dhdrRutEmisor',
        'dhdrDvEmisor',
        'dhdrFolio',
        'dhdrRutRecep',
        'dhdrDvRecep',
        //'diferenciaFecha',
        //  'msgDteCedible',
        'tieneAccesoReceptor',
        'tieneAccesoEmisor',
        'tieneAccesoTenedorVig',
        //'pagadoAlContado',
        'tieneReclamos',
        //'mayorOchoDias',
        'tieneAcuses',
    ];

    /**
     * Events may come inside one of these keys.
     *
     * @var array<array-key, mixed>
     */
    private $_eventKeyNames = ['listEvenHistDoc', 'listaEventosDoc'];

    /**
     * Undocumented function.
     */
    public function __construct(array $responseContents)
    {
        $data = collect($responseContents)->except($this->_discardedFields);

        $data['rutReceptor'] = ($responseContents['dhdrDvRecep'] ?? null) ?
            \implode('-', [$responseContents['dhdrRutRecep'] ?? '', $responseContents['dhdrDvRecep']])
            : $responseContents['dhdrRutRecep'] ?? '';

        $data->except($this->_eventKeyNames)->filter()->each(function ($value, $key): void {
            $this->__set($key, $value);
        });
        $listEvenHistDoc = $data->only($this->_eventKeyNames)->first();

        if (null !== $listEvenHistDoc) {
            $this->setEventos($listEvenHistDoc);
        }
    }

    /**
     * Setting undeclared properties like
     * $response->emiZor = '123' will call this method.
     *
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        $this->_data[$name] = $value;
    }

    /**
     * Returns every stored DTE attribute by merging _data and listEvenHistDoc.
     *
     * @return (EventoHistoricoInstance[]|mixed)[]
     *
     * @psalm-return array{listEvenHistDoc: array<array-key, EventoHistoricoInstance>}
     */
    public function jsonSerialize(): array
    {
        return \array_merge($this->_data, ['listEvenHistDoc' => $this->getEventos()]);
    }

    /**
     * Return only the listEvenHistDoc array.
     *
     * @param mixed $listEvenHistDoc
     *
     * @return static array (sometimes empty) of events
     */
    public function setEventos($listEvenHistDoc)
    {
        $listEvenHistDoc = !\is_array($listEvenHistDoc) ? [$listEvenHistDoc] : $listEvenHistDoc;

        try {
            foreach ($listEvenHistDoc as $evento) {
                $this->listEvenHistDoc[] = $evento instanceof EventoHistoricoInstance ? $evento : new EventoHistoricoInstance((array) $evento);
            }
        } catch (\InvalidArgumentException $e) {
            // invalid events will log an error
            Log::error($e);
        }

        return $this;
    }

    /**
     * Return only the listEvenHistDoc array.
     *
     * @return array<array-key, EventoHistoricoInstance> array (sometimes empty) of events
     */
    public function getEventos()
    {
        return $this->listEvenHistDoc;
    }
}
