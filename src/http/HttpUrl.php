<?php
namespace Mandryn\http;

class HttpUrl {
    public string $uri;
    /** @var array<string|int, mixed> */
    public array $parametersArray;
    public mixed $status = null;
    public mixed $fulfilledData = null;
    public mixed $rejectedData = null;

    /**
     * @param array<string|int, mixed> $parametersArray
     */
    public function __construct(string $uri, array $parametersArray = []) {
        $this->uri = $uri;
        $this->parametersArray = $parametersArray;
    }
}
