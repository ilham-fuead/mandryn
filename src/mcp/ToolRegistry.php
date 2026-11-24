<?php

namespace Mandryn\Mcp;

final class ToolRegistry
{
    /** @var array<string, Tool> */
    private array $tools = [];

    /**
     * @param list<Tool> $tools
     */
    public function __construct(array $tools)
    {
        foreach ($tools as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    public function get(string $name): ?Tool
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * @return list<Tool>
     */
    public function all(): array
    {
        return array_values($this->tools);
    }
}
