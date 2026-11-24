<?php

namespace Mandryn\Mcp;

interface Tool
{
    public function name(): string;

    public function description(): string;

    /**
     * JSON Schema describing the tool's arguments object.
     *
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    /**
     * Execute the tool and return its result payload. The server JSON-encodes
     * the payload into the MCP text content.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     *
     * @throws \Throwable on tool errors (surfaced to the client as isError)
     */
    public function call(array $arguments): array;
}
