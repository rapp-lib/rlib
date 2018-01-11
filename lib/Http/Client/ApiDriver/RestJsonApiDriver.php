<?php
namespace R\Lib\Http\Client\ApiDriver;

class RestJsonApiDriver extends ApiDriver
{
    public function request($uri, $opt=array())
    {
        $response_body = (string)parent::request($uri, $opt)->getBody();
        return json_decode($response_body, true);
    }
}
