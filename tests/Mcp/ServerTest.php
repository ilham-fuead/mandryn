<?php

namespace Tests\Mcp;

use Mandryn\Mcp\Server;
use Mandryn\Mcp\Tool;
use Mandryn\Mcp\ToolRegistry;
use PHPUnit\Framework\TestCase;

final class ServerTest extends TestCase
{
    private function makeEchoTool(): Tool
    {
        return new class implements Tool {
            public function name(): string
            {
                return 'echo';
            }

            public function description(): string
            {
                return 'Echoes its arguments back.';
            }

            public function inputSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => ['message' => ['type' => 'string']],
                ];
            }

            public function call(array $arguments): array
            {
                return ['echoed' => $arguments];
            }
        };
    }

    private function makeThrowingTool(): Tool
    {
        return new class implements Tool {
            public function name(): string
            {
                return 'boom';
            }

            public function description(): string
            {
                return 'Always fails.';
            }

            public function inputSchema(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function call(array $arguments): array
            {
                throw new \RuntimeException('simulated tool failure');
            }
        };
    }

    /**
     * @param list<string> $inputLines
     * @return list<array<string, mixed>>
     */
    private function runServer(array $inputLines, ToolRegistry $registry): array
    {
        $input = fopen('php://memory', 'r+');
        self::assertIsResource($input);
        fwrite($input, implode("\n", $inputLines) . "\n");
        rewind($input);

        $output = fopen('php://memory', 'r+');
        self::assertIsResource($output);

        $server = new Server($registry, 'test-server', '1.2.3', $input, $output);
        $server->run();

        rewind($output);
        $raw = stream_get_contents($output);
        self::assertIsString($raw);

        $responses = [];
        foreach (explode("\n", trim($raw)) as $line) {
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            self::assertIsArray($decoded, "Expected valid JSON response, got: {$line}");
            $responses[] = $decoded;
        }

        return $responses;
    }

    public function testInitializeHandshake(): void
    {
        $responses = $this->runServer(
            ['{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}'],
            new ToolRegistry([$this->makeEchoTool()]),
        );

        self::assertCount(1, $responses);
        self::assertSame(1, $responses[0]['id']);
        self::assertSame('2024-11-05', $responses[0]['result']['protocolVersion']);
        self::assertSame('test-server', $responses[0]['result']['serverInfo']['name']);
        self::assertArrayHasKey('tools', $responses[0]['result']['capabilities']);
    }

    public function testToolsListShape(): void
    {
        $responses = $this->runServer(
            ['{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'],
            new ToolRegistry([$this->makeEchoTool()]),
        );

        self::assertCount(1, $responses);
        $tools = $responses[0]['result']['tools'];
        self::assertCount(1, $tools);
        self::assertSame('echo', $tools[0]['name']);
        self::assertSame('Echoes its arguments back.', $tools[0]['description']);
        self::assertSame('object', $tools[0]['inputSchema']['type']);
    }

    public function testToolsCallSuccess(): void
    {
        $responses = $this->runServer(
            ['{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"echo","arguments":{"message":"hi"}}}'],
            new ToolRegistry([$this->makeEchoTool()]),
        );

        self::assertCount(1, $responses);
        self::assertArrayNotHasKey('isError', $responses[0]['result']);
        $text = $responses[0]['result']['content'][0]['text'];
        self::assertSame(['echoed' => ['message' => 'hi']], json_decode($text, true));
    }

    public function testToolsCallUnknownToolIsError(): void
    {
        $responses = $this->runServer(
            ['{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"nope","arguments":{}}}'],
            new ToolRegistry([$this->makeEchoTool()]),
        );

        self::assertCount(1, $responses);
        self::assertTrue($responses[0]['result']['isError']);
        $text = $responses[0]['result']['content'][0]['text'];
        self::assertStringContainsString('unknown tool', json_decode($text, true)['error']);
    }

    public function testToolsCallThrowingToolIsError(): void
    {
        $responses = $this->runServer(
            ['{"jsonrpc":"2.0","id":5,"method":"tools/call","params":{"name":"boom","arguments":{}}}'],
            new ToolRegistry([$this->makeThrowingTool()]),
        );

        self::assertCount(1, $responses);
        self::assertTrue($responses[0]['result']['isError']);
        $text = $responses[0]['result']['content'][0]['text'];
        self::assertSame('simulated tool failure', json_decode($text, true)['error']);
    }

    public function testNotificationProducesNoResponse(): void
    {
        $responses = $this->runServer(
            ['{"jsonrpc":"2.0","method":"notifications/initialized","params":{}}'],
            new ToolRegistry([]),
        );

        self::assertCount(0, $responses);
    }

    public function testUnknownMethodReturnsMethodNotFound(): void
    {
        $responses = $this->runServer(
            ['{"jsonrpc":"2.0","id":6,"method":"resources/list","params":{}}'],
            new ToolRegistry([]),
        );

        self::assertCount(1, $responses);
        self::assertSame(-32601, $responses[0]['error']['code']);
    }

    public function testInvalidJsonReturnsParseError(): void
    {
        $responses = $this->runServer(
            ['this is not json'],
            new ToolRegistry([]),
        );

        self::assertCount(1, $responses);
        self::assertSame(-32700, $responses[0]['error']['code']);
    }

    public function testPing(): void
    {
        $responses = $this->runServer(
            ['{"jsonrpc":"2.0","id":7,"method":"ping","params":{}}'],
            new ToolRegistry([]),
        );

        self::assertCount(1, $responses);
        self::assertSame([], $responses[0]['result']);
    }

    public function testNullIdRequestStillGetsResponse(): void
    {
        $responses = $this->runServer(
            ['{"jsonrpc":"2.0","id":null,"method":"ping","params":{}}'],
            new ToolRegistry([]),
        );

        self::assertCount(1, $responses);
        self::assertSame([], $responses[0]['result']);
    }

    public function testMultipleRequestsInOneSession(): void
    {
        $responses = $this->runServer(
            [
                '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}',
                '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}',
                '{"jsonrpc":"2.0","method":"notifications/initialized","params":{}}',
                '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"echo","arguments":{"a":1}}}',
            ],
            new ToolRegistry([$this->makeEchoTool()]),
        );

        self::assertCount(3, $responses);
        self::assertSame(1, $responses[0]['id']);
        self::assertSame(2, $responses[1]['id']);
        self::assertSame(3, $responses[2]['id']);
    }
}
