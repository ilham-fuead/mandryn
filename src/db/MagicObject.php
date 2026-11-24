<?php

namespace Mandryn\db;

use JsonSerializable;

/**
 * A lightweight dynamic object: on-the-fly properties backed by a typed array,
 * with row hydration, column-name mapping and JSON serialisation.
 *
 * This is the modern, namespaced counterpart of the legacy global
 * `MagicObject`. It is intended to pair with `Query` (build SQL) and
 * `DBQueryi` (execute + hydrate) so database rows become objects without an
 * ORM.
 */
class MagicObject implements JsonSerializable
{
    /** @var array<string, mixed> */
    private array $property = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->property = $data;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->property[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->property[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->property);
    }

    public function __unset(string $name): void
    {
        unset($this->property[$name]);
    }

    public function set(string $name, mixed $value): self
    {
        $this->property[$name] = $value;

        return $this;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->property[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->property);
    }

    public function remove(string $name): self
    {
        unset($this->property[$name]);

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $columnMap old key => new key
     * @return self
     */
    public static function fromArray(array $data, array $columnMap = []): self
    {
        if ($columnMap !== []) {
            $data = self::mapColumns($data, $columnMap);
        }

        return new self($data);
    }

    /**
     * Hydrate an object from a database row, optionally remapping column names.
     *
     * @param array<string, mixed> $row
     * @param array<string, string> $columnMap
     * @return self
     */
    public static function fromRow(array $row, array $columnMap = []): self
    {
        return self::fromArray($row, $columnMap);
    }

    /**
     * Merge data into the current object and return it.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public function hydrate(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->property[$key] = $value;
        }

        return $this;
    }

    /**
     * Rename properties in place, e.g. ['snake_name' => 'snakeName'].
     *
     * @param array<string, string> $columnMap
     * @return self
     */
    public function applyColumnMap(array $columnMap): self
    {
        $this->property = self::mapColumns($this->property, $columnMap);

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $columnMap
     * @return array<string, mixed>
     */
    private static function mapColumns(array $data, array $columnMap): array
    {
        foreach ($columnMap as $oldKey => $newKey) {
            if ($oldKey !== $newKey && array_key_exists($oldKey, $data)) {
                $data[$newKey] = $data[$oldKey];
                unset($data[$oldKey]);
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->property;
    }

    public function getJsonString(): string
    {
        $json = json_encode($this->property, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? 'null' : $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->property;
    }

    /**
     * Validate values against a filter_var_array definition.
     *
     * @param array<string, mixed> $definition
     * @return self
     */
    public function applyFilterDefinition(array $definition, bool $addEmpty = false): self
    {
        $this->property = filter_var_array($this->property, $definition, $addEmpty);

        return $this;
    }

    public function count(): int
    {
        return count($this->property);
    }
}
