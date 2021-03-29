<?php

/**
 * DBThor Cesion 1.11.0
 */

namespace Tests\Helpers;

use Tests\Helpers\SiiDOMDocument;
use CTOhm\SiiAsyncClients\Util\Misc;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property-read   bool $validPublicKey,
 * @property-read   bool $matchingDigests,
 * @property-read  object $schemaCheck,
 * @property-read  SiiDOMDocument $EntityDocument,
 * @property-read  string $c14n_referenced
 * @property-read  string $digestValue
 * @property-read  string $computedDigest
 */
class SiiDocumentValidationResult implements \JsonSerializable
{
    private bool $validPublicKey;
    private bool $matchingDigests;
    private object $schemaCheck;
    private SiiDOMDocument $EntityDocument;
    private string $c14n_referenced;
    private array $errors = [];
    private string    $digestValue = '';
    private string $computedDigest = '';
    private string $referenceNodeName;

    public function __construct(
        bool $validPublicKey,
        string $digestValue,
        string $computedDigest,
        ?object $schemaCheck,
        SiiDOMDocument $EntityDocument,
        string $referenceNodeName = '',
        ?string $referenceNodeC14N = null
    ) {
        $this->validPublicKey = $validPublicKey;
        $this->matchingDigests = $digestValue === $computedDigest;
        $this->referenceNodeName = $referenceNodeName;
        $this->EntityDocument = $EntityDocument;
        if ($schemaCheck) {
            $this->setSchemaCheck($schemaCheck);
        }
        $this->digestValue = $digestValue;
        $this->computedDigest = $computedDigest;
        $this->c14n_referenced = $referenceNodeC14N ?? $EntityDocument->getReferencedNode($referenceNodeName)->C14N();
    }
    public function getReferenceNodeC14N()
    {
        return $this->c14n_referenced;
    }

    public function getEntityDocument()
    {
        return $this->EntityDocument;
    }
    public function setSchemaCheck(object $schemaCheck): self
    {
        $this->schemaCheck = $schemaCheck;

        return $this;
    }
    public function tap(callable $cb)
    {
        return tap($this, $cb);
    }

    public function computeErrors(): array
    {
        $this->errors = [];

        if (!$this->validPublicKey) {
            $this->errors[] = Misc::INVALID_PUBLIC_KEY_MSG;
        }

        if (!$this->matchingDigests) {
            $this->errors[] = Misc::MISMATCHED_DIGESTS_MSG;
        }
        if (!$this->schemaCheck->is_valid) {
            foreach ($this->schemaCheck->errors as $schemaError) {
                $schemaErrorStr = $schemaError;

                if ($schemaError instanceof Throwable) {
                    $schemaErrorStr = $schemaError->getMessage();
                } elseif (is_string($schemaError)) {
                    $schemaErrorStr = trim($schemaError);
                }
                $this->errors[] = $schemaErrorStr;
            }
        }

        return $this->errors;
    }

    public function __get(string $key)
    {
        $snake_cased = mb_strtoupper(Str::snake($key));

        $constVal = constant(
            sprintf('%s::%s', Misc::class, $snake_cased)
        );
        //kdump([$snake_cased => $constVal]);
        return $this->jsonSerialize()[$constVal];
    }

    public function jsonSerialize()
    {
        return  [
            Misc::VALID_PUBLIC_KEY => $this->validPublicKey,
            Misc::MATCHING_DIGESTS => $this->matchingDigests,
            Misc::VALID_SCHEMA => $this->schemaCheck->is_valid,
            Misc::DIGEST_VALUE => $this->digestValue,
            Misc::COMPUTED_DIGEST => $this->computedDigest,
            'xml_iso' => self::isIso($this->EntityDocument->saveXML()),
            'c14n_utf8' => self::isUtf($this->c14n_referenced),
            'referenceNodeClassName' => get_class($this->EntityDocument->getReferencedNode()),

        ];
    }

    /**
     * UTF Encoding Definition Method.
     *
     * @param string $text
     *
     * @return bool
     */
    public static function isUtf(string $text): bool
    {
        return (bool) preg_match('/./u', $text);
    }
    public static function isISO(string $string_data)
    {
        return $string_data === utf8_decode(utf8_encode($string_data));
    }

    public static function isUTF8(string $string_data)
    {
        return $string_data === utf8_encode(utf8_decode($string_data));
    }

    public function isValid(): bool
    {
        return $this->validPublicKey && $this->matchingDigests;
    }

    public function collect(?string $documentKey = null): Collection
    {
        $documentKey = $documentKey ??= $this->EntityDocument->getName();
        $this->errors = $this->computeErrors();

        return collect($this->jsonSerialize())
            ->merge([
                'tagName' => $documentKey,
                'valid' => $this->validPublicKey && $this->matchingDigests,
                'errors' => count(($this->errors)) > 0 ? [$documentKey => $this->errors] : [],
            ]);
    }

    public function dump()
    {
        $formatForOutput = collect($this->jsonSerialize());
        $formatForOutput->keys()->combine(
            $formatForOutput->values()->map(fn ($value) => is_bool($value) ? ($value ? Misc::BOOL_TO_VALID : Misc::BOOL_TO_INVALID) : $value)
        );
        if (app()->runningInConsole()) {
            return $formatForOutput;
        }
        kdump($formatForOutput);
    }
}
