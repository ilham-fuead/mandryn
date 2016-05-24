<?php
namespace Mandryn\http;

class HttpUrl{
    public $uri;
    public $parametersArray;
    public $status;
    public $fulfilledData;
    public $rejectedData;    
    
    public function __construct($uri,array $parametersArray=[]) {
        $this->uri=$uri;
        $this->parametersArray=$parametersArray;
    }
}

