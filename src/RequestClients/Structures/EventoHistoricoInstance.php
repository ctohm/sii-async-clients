<?php

declare(strict_types=1);

/**
 * CTOhm - SII Async Clients
 */

namespace CTOhm\SiiAsyncClients\RequestClients\Structures;

use InvalidArgumentException;
use JsonSerializable;

 /**
  * Structure to ensure normalization and type hinting of a single event.
  *
  * @property string $codEvento
  * @property string $descEvento
  * @property string $dvResponsable
  * @property string $rutResponsable
  */
 class EventoHistoricoInstance implements JsonSerializable
 {
     public string $descEvento;

     public string $codEvento;

     public string $fechaEvento;

     public string $rutResponsable;

     private array $_mandatoryFields = ['descEvento', 'codEvento', 'fechaEvento', 'rutResponsable'];

     /**
      * Undocumented function.
      *
      * @param array{descEvento: string, codEvento: string, fechaEvento: string, rutResponsable: string, dvResponsable?: string} $eventArray
      */
     public function __construct(array $eventArray)
     {
         // ensure constructor argument has mandatory fields (only dvResponsable is optional)
         foreach ($this->_mandatoryFields as $fieldName) {
             if (!\array_key_exists($fieldName, $eventArray)) {
                 throw new InvalidArgumentException(\sprintf('Evento inválido, no tiene campo %s', $fieldName));
             }
         }
         // rutResponsable is the concatenation of rutResponsable-rutResponsable if rutResponsable is present, or rutResponsable otherwise
         $this->rutResponsable = ($eventArray['dvResponsable'] ?? null) ?
            \implode('-', [$eventArray['rutResponsable'], $eventArray['dvResponsable']])
            : $eventArray['rutResponsable'];
         $this->descEvento = $eventArray['descEvento'];
         $this->codEvento = $eventArray['codEvento'];
         $this->fechaEvento = $eventArray['fechaEvento'];
     }

     /**
      * Canonica array representation of this event.
      */
     public function jsonSerialize()
     {
         return [
             'rutResponsable' => $this->rutResponsable,
             'descEvento' => $this->descEvento,
             'codEvento' => $this->codEvento,
             'fechaEvento' => $this->fechaEvento,
         ];
     }
 }
