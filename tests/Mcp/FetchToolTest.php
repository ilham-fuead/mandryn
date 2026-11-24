<?php

namespace Tests\Mcp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response;
use Mandryn\Mcp\GuardException;
use Mandryn\Mcp\Tools\FetchTool;
use PHPUnit\Framework\TestCase;

final class FetchToolTest extends TestCase
{
    /**
     * @param array<int, Response|ConnectException> $queue
     * @param list<array{request: PsrRequest}>|null $history
     */
    private function makeTool(array $queue, ?array &$history = null): FetchTool
    {
        $mock = new MockHandler($queue);
        $stack = HandlerStack::create($mock);
        if (func_num_args() >= 2) {
            $stack->push(Middleware::history($history));
        }
        $client = new Client(['handler' => $stack]);

        return new FetchTool($client);
    }

    public function testFetchReturnsStatusAndBody(): void
    {
        $tool = $this->makeTool([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);

        $result = $tool->call(['url' => 'https://example.com/api']);

        self::assertSame(200, $result['status']);
        self::assertSame('application/json', $result['content_type']);
        self::assertSame('{"ok":true}', $result['body']);
        self::assertSame(11, $result['bytes']);
        self::assertFalse($result['truncated']);
    }

    public function testQueryParamsAreForwarded(): void
    {
        $history = [];
        $tool = $this->makeTool([
            new Response(200, [], 'ok'),
        ], $history);

        $tool->call(['url' => 'https://example.com/search', 'params' => ['q' => 'mandryn', 'page' => 2]]);

        $uri = (string) $history[0]['request']->getUri();
        self::assertStringContainsString('q=mandryn', $uri);
        self::assertStringContainsString('page=2', $uri);
    }

    public function testOversizedBodyIsTruncated(): void
    {
        $tool = $this->makeTool([
            new Response(200, [], str_repeat('a', 70000)),
        ]);

        $result = $tool->call(['url' => 'https://example.com/big']);

        self::assertTrue($result['truncated']);
        self::assertSame(65536, $result['bytes']);
        self::assertSame(65536, strlen($result['body']));
    }

    public function testNonHttpSchemeIsRejected(): void
    {
        $tool = $this->makeTool([]);

        foreach (['ftp://example.com/x', 'file:///etc/passwd', 'gopher://x'] as $url) {
            try {
                $tool->call(['url' => $url]);
                self::fail('Expected GuardException for ' . $url);
            } catch (GuardException $e) {
                self::assertStringContainsString('only http/https', $e->getMessage());
            }
        }

        self::assertTrue(true);
    }

    public function testNon2xxStatusIsReturnedNotThrown(): void
    {
        $tool = $this->makeTool([
            new Response(404, [], 'not found'),
        ]);

        $result = $tool->call(['url' => 'https://example.com/missing']);

        self::assertSame(404, $result['status']);
        self::assertSame('not found', $result['body']);
    }

    public function testConnectErrorBecomesRuntimeException(): void
    {
        $tool = $this->makeTool([
            new ConnectException('connection refused', new PsrRequest('GET', 'https://example.com')),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fetch failed');
        $tool->call(['url' => 'https://example.com']);
    }

    public function testMissingUrlThrows(): void
    {
        $tool = $this->makeTool([]);

        $this->expectException(\InvalidArgumentException::class);
        $tool->call([]);
    }

    public function testSchemaShape(): void
    {
        $tool = $this->makeTool([]);

        self::assertSame('mandryn_fetch', $tool->name());
        $schema = $tool->inputSchema();
        self::assertSame('object', $schema['type']);
        self::assertArrayHasKey('url', $schema['properties']);
        self::assertArrayHasKey('params', $schema['properties']);
    }
}
