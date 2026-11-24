<?php

namespace Mandryn\Mcp\Tools;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Mandryn\http\HttpUrl;
use Mandryn\Mcp\GuardException;
use Mandryn\Mcp\Tool;

final class FetchTool implements Tool
{
    private const BODY_CAP_BYTES = 65536;
    private const TIMEOUT_SECONDS = 10;

    public function __construct(private readonly ClientInterface $client = new Client())
    {
    }

    public function name(): string
    {
        return 'mandryn_fetch';
    }

    public function description(): string
    {
        return 'Fetch a single HTTP(S) URL with GET. Query parameters are built with '
            . 'Mandryn HttpUrl. Returns status, content type and the body capped at 64 KB. '
            . 'Only http and https schemes are allowed.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'The http/https URL to fetch.',
                ],
                'params' => [
                    'type' => 'object',
                    'description' => 'Optional query-string parameters.',
                    'default' => new \stdClass(),
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function call(array $arguments): array
    {
        $url = $arguments['url'] ?? null;
        if (!is_string($url) || trim($url) === '') {
            throw new \InvalidArgumentException('missing required argument "url" (non-empty string)');
        }

        $params = $arguments['params'] ?? [];
        if (!is_array($params)) {
            throw new \InvalidArgumentException('argument "params" must be an object of query parameters');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!is_string($scheme) || !in_array(strtolower($scheme), ['http', 'https'], true)) {
            throw new GuardException('only http/https URLs are allowed (got scheme: ' . var_export($scheme, true) . ')');
        }

        $httpUrl = new HttpUrl($url, $params);

        try {
            $response = $this->client->request('GET', $httpUrl->uri, [
                'query' => $httpUrl->parametersArray,
                'timeout' => self::TIMEOUT_SECONDS,
                'allow_redirects' => true,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('fetch failed: ' . $e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();
        $truncated = strlen($body) > self::BODY_CAP_BYTES;
        if ($truncated) {
            $body = substr($body, 0, self::BODY_CAP_BYTES);
        }

        $contentType = $response->getHeaderLine('Content-Type');

        return [
            'status' => $response->getStatusCode(),
            'content_type' => $contentType !== '' ? $contentType : null,
            'bytes' => strlen($body),
            'truncated' => $truncated,
            'body' => $body,
        ];
    }
}
