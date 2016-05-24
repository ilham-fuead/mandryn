<?php

namespace Mandryn\http;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Mandryn\http\HttpUrl;

class ConcurrentRequest {

    private $httpUrlObjArray;
    private $client;
    private $requests;

    public function __construct() {
        $this->httpUrlObjArray = [];
        $this->client = new Client();
    }

    public function addUrl($url, array $parametersArray = []) {
        $this->httpUrlObjArray[] = new HttpUrl($uri, $parametersArray);
    }

    private function prepareRequest() {
        $this->requests = function (array $httpUrlObjArray) {
            foreach ($httpUrlObjArray as $httpUrlObj) {
                $uri = $httpUrlObj->uri;

                yield new Request('GET', $uri . '?' . http_build_query($httpUrlObj->parametersArray));
            }
        };
    }

    public function initPoolRequest() {
        $this->prepareRequest();
        
        $httpUrlObjArray=[];
        
        $pool = new Pool($this->client, $this->requests($this->httpUrlObjArray), [
            'concurrency' => 10,
            'fulfilled' => function ($response, $index) use (&$httpUrlObjArray) {
                // this is delivered each successful response
                $body = $response->getBody();
                $httpUrlObjArray[$index]['fulfilled'] = (string) $body;
                // Implicitly cast the body to a string and echo it
                //echo "$index $body<br>";
            },
            'rejected' => function ($reason, $index) use (&$httpUrlObjArray){
                // this is delivered each failed request
                //echo "Error at $index $reason<br>";
                $httpUrlObjArray[$index]['rejected'] = $reason;
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
        
        //var_dump($httpUrlObjArray);
        return $httpUrlObjArray;
    }

}