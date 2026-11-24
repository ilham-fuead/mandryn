<?php

namespace Mandryn\http;

use Mandryn\db\MagicObject;

/**
 * Modern, namespaced input validator. Decoupled from any database connection
 * and from `$_GET`/`$_POST`/superglobals: it only validates and transforms
 * the data it is given, so it can be unit-tested and reused in any SAPI.
 *
 * It extends the modern `MagicObject` so validated data can be serialised,
 * renamed and consumed like any other row object.
 */
class MagicInput extends MagicObject
{
    /** @var list<array{name: string, type: string, required: bool, alias: string}> */
    private array $inputDefinition = [];

    private bool $removeNonDefineInput = true;
    private bool $isDefinitionExist = false;

    /** @var list<array{name: string, error: string}> */
    private array $nonCompliedInputList = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Define which keys are acceptable and how each must look.
     *
     * Each definition: [name, type, required, alias].
     *
     * Type codes (kept compatible with the legacy API):
     *   i int, f float, n numeric, d date, dt datetime, e email,
     *   h/hs header (sanitised), s/u string.
     *
     * @param list<array{0: string, 1: string, 2?: bool, 3?: string}> $definitions
     */
    public function setInputsDefinition(array $definitions, bool $removeNonDefineInput = true): self
    {
        $this->inputDefinition = [];
        $this->isDefinitionExist = true;
        $this->removeNonDefineInput = $removeNonDefineInput;

        foreach ($definitions as $def) {
            $this->inputDefinition[] = [
                'name' => $def[0],
                'type' => $def[1],
                'required' => (bool) ($def[2] ?? false),
                'alias' => $def[3] ?? '',
            ];
        }

        return $this;
    }

    /**
     * @return list<array{name: string, type: string, required: bool, alias: string}>
     */
    public function getInputsDefinition(): array
    {
        return $this->inputDefinition;
    }

    /**
     * @return list<array{name: string, error: string}>
     */
    public function getNonCompliedInputList(): array
    {
        return $this->nonCompliedInputList;
    }

    public function isInputsComplied(): bool
    {
        $this->applyInputDefinition();

        return $this->nonCompliedInputList === [];
    }

    public function applyInputDefinition(): self
    {
        $this->nonCompliedInputList = [];
        $data = $this->toArray();

        foreach ($this->inputDefinition as $def) {
            $name = $def['name'];
            if ($def['type'] === 'h' || $def['type'] === 'hs') {
                $name = strtoupper(str_replace('-', '_', $name));
            }

            if (!array_key_exists($name, $data)) {
                if ($def['required']) {
                    $this->nonCompliedInputList[] = ['name' => $name, 'error' => 'Required'];
                }
                continue;
            }

            $value = $data[$name];
            if (($value === null || trim((string) $value) === '') && $value !== '0' && $value !== 0) {
                $this->nonCompliedInputList[] = ['name' => $name, 'error' => 'Empty or null'];
                continue;
            }

            $this->inputTypeChecker($name, $value, $def['type']);
        }

        if ($this->removeNonDefineInput) {
            $validNames = [];
            foreach ($this->inputDefinition as $def) {
                $name = $def['name'];
                if ($def['type'] === 'h' || $def['type'] === 'hs') {
                    $name = strtoupper(str_replace('-', '_', $name));
                }
                $validNames[] = $name;
            }

            foreach ($data as $key => $value) {
                if (!in_array($key, $validNames, true)) {
                    unset($this->{$key});
                }
            }
        }

        return $this;
    }

    public function getJsonString(): string
    {
        if ($this->isDefinitionExist === false) {
            return parent::getJsonString();
        }

        if ($this->isInputsComplied()) {
            return parent::getJsonString();
        }

        return '[]';
    }

    public function toArray(): array
    {
        if ($this->isDefinitionExist === false) {
            return parent::toArray();
        }

        if ($this->isInputsComplied()) {
            return parent::toArray();
        }

        return [];
    }

    private function inputTypeChecker(string $name, mixed $value, string $type): void
    {
        switch ($type) {
            case 'i':
                $this->numericTypeChecker($name, $value, 'Not an integer', 'i');
                break;
            case 'f':
                $this->numericTypeChecker($name, $value, 'Not a float', 'f');
                break;
            case 'n':
                $this->numericTypeChecker($name, $value, 'Not a number', 'n');
                break;
            case 'e':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->nonCompliedInputList[] = ['name' => $name, 'error' => 'Invalid e-mail'];
                }
                break;
            case 'd':
                $this->datetimeTypeChecker($name, (string) $value, 'Invalid date', 'Y-m-d');
                break;
            case 'dt':
                $this->datetimeTypeChecker($name, (string) $value, 'Invalid datetime', 'Y-m-d H:i:s');
                break;
            case 'hs':
                $this->safeHeaderValueChecker($name, (string) $value, 'Unsecured header value');
                break;
            case 'h':
            case 'u':
            case 's':
                break;
        }
    }

    private function numericTypeChecker(string $name, mixed $value, string $message, string $type): void
    {
        if (!is_numeric($value)) {
            $this->nonCompliedInputList[] = ['name' => $name, 'error' => $message];
            return;
        }

        $value += 0;

        switch ($type) {
            case 'i':
                if (!is_int($value)) {
                    $this->nonCompliedInputList[] = ['name' => $name, 'error' => $message];
                }
                break;
            case 'f':
                if (!is_float($value)) {
                    $this->nonCompliedInputList[] = ['name' => $name, 'error' => $message];
                }
                break;
        }
    }

    private function datetimeTypeChecker(string $name, string $value, string $message, string $format): void
    {
        $d = \DateTime::createFromFormat($format, $value);
        if (!($d && $d->format($format) === $value)) {
            $this->nonCompliedInputList[] = ['name' => $name, 'error' => $message];
        }
    }

    private function safeHeaderValueChecker(string $name, string $value, string $message): void
    {
        if (ctype_alnum(str_replace(['-', '_', '.', ',', ':', '/', '@'], '', $value)) === false) {
            $this->nonCompliedInputList[] = ['name' => $name, 'error' => $message];
        }
    }
}
