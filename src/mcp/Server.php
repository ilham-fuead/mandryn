<?php

namespace Mandryn\Mcp;

final class Server
{
    /** @var resource|null */
    private $input;
    /** @var resource|null */
    private $output;

    /**
     * @param resource|null $input  stream to read JSON-RPC lines from (defaults to STDIN)
     * @param resource|null $output stream to write JSON-RPC lines to (defaults to STDOUT)
     */
    public function __construct(
        private readonly ToolRegistry $registry,
        private readonly string $name = 'mandryn',
        private readonly string $version = '0.1.0',
        $input = null,
        $output = null,
    ) {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Run the newline-delimited JSON-RPC 2.0 loop until EOF on input.
     */
    public function run(): void
    {
        $input = $this->input ?? STDIN;

        while (($line = fgets($input)) !== false) {
            $line = trim($line);
            // Some pipes/clients prepend a UTF-8 BOM (PowerShell's stdin pipe
            // can even send it twice); strip defensively.
            while (str_starts_with($line, "\xEF\xBB\xBF")) {
                $line = substr($line, 3);
            }
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $message = json_decode($line, true);
            if (!is_array($message)) {
                $this->writeError(null, -32700, 'Parse error');
                continue;
            }

            // A JSON-RPC notification has no "id" member at all.
            if (!array_key_exists('id', $message)) {
                $this->handleNotification((string) ($message['method'] ?? ''));
                continue;
            }

            $id = $message['id'];
            $method = is_string($message['method'] ?? null) ? $message['method'] : '';
            $params = is_array($message['params'] ?? null) ? $message['params'] : [];

            $this->dispatch($id, $method, $params);
        }
    }

    /**
     * @param mixed $id
     * @param array<string, mixed> $params
     */
    private function dispatch(mixed $id, string $method, array $params): void
    {
        switch ($method) {
            case 'initialize':
                $this->writeResult($id, [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => ['tools' => new \stdClass()],
                    'serverInfo' => ['name' => $this->name, 'version' => $this->version],
                ]);
                return;

            case 'ping':
                $this->writeResult($id, new \stdClass());
                return;

            case 'tools/list':
                $tools = [];
                foreach ($this->registry->all() as $tool) {
                    $tools[] = [
                        'name' => $tool->name(),
                        'description' => $tool->description(),
                        'inputSchema' => $tool->inputSchema(),
                    ];
                }
                $this->writeResult($id, ['tools' => $tools]);
                return;

            case 'tools/call':
                $this->writeResult($id, $this->handleToolCall($params));
                return;

            default:
                $this->writeError($id, -32601, 'Method not found');
                return;
        }
    }

    private function handleNotification(string $method): void
    {
        // MCP lifecycle notifications are acknowledged by doing nothing.
        // notifications/initialized, notifications/cancelled, and anything
        // else a client may send fall through here silently.
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function handleToolCall(array $params): array
    {
        $toolName = is_string($params['name'] ?? null) ? $params['name'] : '';
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        $tool = $this->registry->get($toolName);
        if ($tool === null) {
            return [
                'content' => [
                    ['type' => 'text', 'text' => $this->encode(['error' => 'unknown tool: ' . $toolName])],
                ],
                'isError' => true,
            ];
        }

        try {
            $result = $tool->call($arguments);

            return [
                'content' => [
                    ['type' => 'text', 'text' => $this->encode($result)],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'content' => [
                    ['type' => 'text', 'text' => $this->encode(['error' => $e->getMessage()])],
                ],
                'isError' => true,
            ];
        }
    }

    /**
     * @param mixed $payload
     */
    private function encode(mixed $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? '{"error":"failed to encode tool result"}' : $json;
    }

    /**
     * @param mixed $id
     * @param mixed $result
     */
    private function writeResult(mixed $id, mixed $result): void
    {
        $this->write(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
    }

    /**
     * @param mixed $id
     */
    private function writeError(mixed $id, int $code, string $message): void
    {
        $this->write(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function write(array $payload): void
    {
        $output = $this->output ?? STDOUT;
        fwrite($output, $this->encode($payload) . "\n");
        fflush($output);
    }
}
