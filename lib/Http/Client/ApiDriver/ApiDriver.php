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
        $uri = $this->config["base_uri"].$uri;
        $method = $opt["method"] ?: "GET";
        $headers = $opt["headers"] ?: array();
        $body = $opt["body"] ?: "";
        $client = new \Guzzle\Http\Client();
        $response = $client->createRequest($method, $uri, $headers, $body, $opt)->send();
        return $response;
    }
}
