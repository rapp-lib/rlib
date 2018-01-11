<?php
namespace R\Lib\Http\Client;

class ApiDriver
{
    protected $config;
    public function __construct($config)
    {
        $this->config = $config;
    }
    public function request($uri, $opt=array())
    {
        $method = $opt["method"] ?: "GET";
        $headers = $opt["headers"] ?: array();
        $body = $opt["body"] ?: null;
        $client = new \Guzzle\Http\Client($this->config["base_uri"]);
        return $client->createRequest($method, $uri, $headers, $body, $opt)->send();
    }
}
