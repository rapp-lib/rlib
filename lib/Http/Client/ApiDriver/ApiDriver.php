<?php
namespace R\Lib\Http\Client\ApiDriver;

class ApiDriver
{
    protected $config;
    public function __construct($config)
    {
        $this->config = $config;
    }
    public function request($uri, $opt=array())
    {
        return $this->httpRequest($uri, $opt);
    }
    public function httpRequest($uri, $opt=array())
    {
        $method = $opt["method"] ?: "GET";
        $headers = $opt["headers"] ?: array();
        $body = $opt["body"] ?: null;
        $client = new \Guzzle\Http\Client;
        return $client->createRequest($method, $uri, $headers, $body, $opt)->send();
    }
}
