<?php
namespace R\Lib\Http\Client\ApiDriver;

// Guzzle3
use Guzzle\Http\Client;
// Oauth1
use Guzzle\Plugin\Oauth\OauthPlugin;
// Oauth2
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use CommerceGuys\Guzzle\Oauth2\Oauth2Subscriber;

class ApiDriver
{
    protected $config;
    public function __construct($config)
    {
        $this->config = $config;
    }
    public function request($uri, $opt=array())
    {
        $client_opt = $this->config["client_option"] ?: array();
        $uri = $this->config["base_uri"].$uri;
        $method = $opt["method"] ?: "GET";
        $headers = $opt["headers"] ?: array();
        $body = $opt["body"] ?: "";
        $client = new Client($client_opt);
        // Oauth1
        // http://guzzle3.readthedocs.io/plugins/oauth-plugin.html
        if ($oauth1 = $this->config["oauth1"]) {
            // 'consumer_key'    => 'my_key',
            // 'consumer_secret' => 'my_secret',
            // 'token'           => 'my_token',
            // 'token_secret'    => 'my_token_secret'
            $client->addSubscriber(new OauthPlugin($oauth1));
        }
        // Oauth2 Resource Owner Password Credentials Grant
        // https://github.com/commerceguys/guzzle-oauth2-plugin
        // https://qiita.com/mapyo/items/cf789276025f9b9285fa
        if ($oauth2 = $this->config["oauth2"]) {
            $oauth_client = new Client($client_opt);
            if ($oauth2["grant_type"]==="password") {
                $credentials = new PasswordCredentials($oauth_client, $oauth2);
            }
            $refresh_token = new RefreshToken($oauth_client, $oauth2);
            $oauth2_subscliber = new Oauth2Subscriber($credentials, $refresh_token);
            $client->addSubscriber($oauth2_subscliber);
        }
        $response = $client->createRequest($method, $uri, $headers, $body, $opt)->send();
        return $response;
    }
}
