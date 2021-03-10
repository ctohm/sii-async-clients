<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use JsonSerializable;

/**
 * Structure for the response that SII returns when
 * requesting Factibilidad Cesion through the web
 * interface. Useful for retrieving extra info  and type checking.
 *
 * @property int $diferenciaFecha
 * @property string $dvEmisor
 * @property string $dvReceptor
 * @property string $fechaEmision
 * @property string $fechaRecepcion
 * @property int $folio
 * @property bool $mayorOchoDias
 * @property int $montoTotal
 * @property string $msgDteCedible
 * @property string $rutEmisor
 * @property string $rutReceptor
 * @property string $rzEmisor
 * @property string $rzReceptor
 * @property string $siiToken
 * @property int $tipoDoc
 */
class DteCedibleResponse implements Arrayable, JsonSerializable
{
    private array $_keysDict = [
        'dhdrCodigo' => 'codigo',
        'dhdrRutEmisor' => 'rutEmisor',
        'dhdrDvEmisor' => 'dvEmisor',

        'dtdcCodigo' => 'tipoDoc',

        'dhdrFolio' => 'folio',
        'dhdrRutRecep' => 'rutReceptor',
        'dhdrDvRecep' => 'dvReceptor',

        'dhdrFchEmis' => 'fechaEmision',
        'dhdrMntTotal' => 'montoTotal',
        'dhdrIva' => 'mntIva',
        'dtecTmstRecep' => 'fechaRecepcion',

        'listEvenHistDoc' => null,
    ];

    private array $shouldBeIntegers = [
        'montoTotal',
        'dhdrMntTotal',
        'dhdrIva',
        'mntIva',
        'dtdcCodigo',
        'tipoDoc',
        'folio',
        'diferenciaFecha',
    ];

    private array $onlyKeys = [
        'fechaEmision',
        'montoTotal',
        'fechaRecepcion',
        'diferenciaFecha',
        'mayorOchoDias',
        'msgDteCedible',
        'rutReceptor',
        'rutEmisor',
        'dvReceptor',
        'rzReceptor',
        'rzEmisor',
        'dvEmisor',
        'rutEmisor',
        'tipoDoc',
        'folio',
        'siiToken',
    ];

    private Collection $_data;

    public function __construct(Enumerable $_data)
    {
        $this->_data = $_data->map(function ($value, $key) {
            $key = $this->_keysDict[$key] ?? $key;

            if (\in_array($key, $this->shouldBeIntegers, true)) {
                $value = (int) $value;
            }

            return (object) ['key' => $key, 'value' => $value];
        })->reduce(static fn ($accum, $kv) => $accum->put($kv->key, $kv->value), collect([]));

        /*$resArr = collect($res)->except([
        'dhdrMntTotal',
    ])->merge([
        'montoTotal' => $res['dhdrMntTotal'],
    ])->shallowKeysToCamelCase();*/
    }

    /**
     * All properties of this class are private, therefore properties must be assigned with __set.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function __set(string $name, $value): self
    {
        $this->_data[$name] = $value;

        return $this;
    }

    /**
     * All properties of this class are private, therefore properties must be accessed with __get.
     *
     * @return mixed $value
     */
    public function __get(string $name)
    {
        return $this->jsonSerialize()[$name] ?? null;
    }

    public function collect(): Collection
    {
        return $this->_data;
    }

    public function toArray(): array
    {
        $collection = $this->collect();
        $rutEmisor = $collection->only(['rutEmisor', 'dvEmisor'])->join('-');
        $rutReceptor = $collection->only(['rutReceptor', 'dvReceptor'])->join('-');

        return $collection->replace([
            'rutEmisor' => $rutEmisor, 'rutReceptor' => $rutReceptor,
        ])->only($this->onlyKeys)
            ->filter()
            ->toArray();
    }

    public function tap(Closure $callable): Collection
    {
        return $this->collect()->tap($callable);
    }

    public function kdump(): Collection
    {
        return $this->tap(static fn ($res) => kdump($res->filter()->all()));
    }

    /**
     * Returns every stored DTE attribute by merging _data and listEvenHistDoc.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
