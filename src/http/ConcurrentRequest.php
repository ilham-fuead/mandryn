<?php

namespace Mandryn\http;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class ConcurrentRequest {

    /** @var list<HttpUrl> */
    private array $httpUrlObjArray = [];
    private Client $client;
    private int $maxConcurrentRequest;

    public function __construct(int $maxConcurrentRequest = 10) {
        $this->httpUrlObjArray = [];
        $this->client = new Client();
        $this->maxConcurrentRequest = $maxConcurrentRequest;
    }

    /**
     * @param array<string|int, mixed> $parametersArray
     */
    public function addUrl(string $uri, array $parametersArray = []): void {
        $this->httpUrlObjArray[] = new HttpUrl($uri, $parametersArray);
    }

    public function getUrlListArray(): \Generator {
        foreach ($this->httpUrlObjArray as $httpUrlObj) {
            yield $httpUrlObj->uri . ((sizeof($httpUrlObj->parametersArray)>0)?'?':'') . http_build_query($httpUrlObj->parametersArray);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function initPoolRequest(): array {
        /** @var array<int, array<string, mixed>> $httpUrlObjArray */
        $httpUrlObjArray = [];

        /**
         * @param list<HttpUrl> $httpUrlObjArray
         */
        $requests = function (array $httpUrlObjArray): \Generator {
            foreach ($httpUrlObjArray as $httpUrlObj) {
                $uri = $httpUrlObj->uri;

                yield new Request('GET', $uri . '?' . http_build_query($httpUrlObj->parametersArray));
            }
        };

        $pool = new Pool($this->client, $requests($this->httpUrlObjArray), [
            'concurrency' => $this->maxConcurrentRequest,
            'fulfilled' => function (\Psr\Http\Message\ResponseInterface $response, int $index) use (&$httpUrlObjArray): void {
                // this is delivered each successful response
                $body = $response->getBody();
                $httpUrlObjArray[$index]['fulfilled'] = (string) $body;
            },
            'rejected' => function (\Throwable $reason, int $index) use (&$httpUrlObjArray): void {
                // this is delivered each failed request
                $httpUrlObjArray[$index]['rejected'] = $reason;
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        return $httpUrlObjArray;
    }

}
