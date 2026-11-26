<?php

declare(strict_types=1);

namespace Mandryn\Mcp;

/**
 * A read-only MCP resource exposing a local documentation file.
 */
final class Resource
{
    public function __construct(
        public readonly string $uri,
        public readonly string $name,
        public readonly string $description,
        public readonly string $mimeType,
        public readonly string $path,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'name' => $this->name,
            'description' => $this->description,
            'mimeType' => $this->mimeType,
        ];
    }
}
