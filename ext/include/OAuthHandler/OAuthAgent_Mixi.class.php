<?php


//-------------------------------------
//
class OAuthAgent_Mixi {

    protected $handler;
    protected $consumer_key;
    protected $consumer_secret;

    protected $get_access_token_url
            ='https://secure.mixi-platform.com/2/token';
    protected $authorize_token_url
            ='https://mixi.jp/connect_authorize.pl';
    protected $graph_url
            ='http://api.mixi-platform.com/2/people/@me/@self';

    //-------------------------------------
    //
    public function __construct ($handler, $params) {

        $this->handler =$handler;
        $this->consumer_key =$params["consumer_key"];
        $this->consumer_secret =$params["consumer_secret"];
    }

    //-------------------------------------
    //
    public function auth ($params) {

        $callback_code =$params["get_vars"]["code"];
        $callback_url =$params["callback_url"];
        $scope =$params["scope"];

        $result =array();

        // SecretとCallbackしたパラメータでACEESS-TOKENを取得
        if ($callback_code) {

            // ACEESS-TOKENを取得
            $request_handler =new HTTPRequestHandler;
            $response =$request_handler->request($this->get_access_token_url,array(
                "post" =>array(
                    'grant_type' =>'authorization_code',
                    'client_id' =>$this->consumer_key,
                    'client_secret' =>$this->consumer_secret,
                    'redirect_uri' =>$callback_url,
                    'code' =>$callback_code,
                ),
            ));
            $result_json =json_decode($response["body"]);
            report($result_json);

            $result["oauth_token"] =$result_json["access_token"];
            $result["oauth_token_secret"] ="";
            $result["oauth_token_expire"] =time()+$result_json["expires_in"];
            $result["refresh_token"] =$result_json["refresh_token"];

            // UserIDの取得
            $response =obj("HTTPRequestHandler")->request(
                    $this->graph_url.'?oauth_token='.$result["oauth_token"]);
            $result_json =json_decode($response["body"]);

            $result["oauth_uid"] =$result_json["entry"]["id"];
            $result["profile"] =$result_json["entry"];
        }

        // ACCESS-TOKENがなければ認証リンクを表示
        if ( ! $result["oauth_token"]) {

            $query =http_build_query(array(
                'response_type' =>'code',
                'display' =>'pc',
                'client_id' =>$this->consumer_key,
                'redirect_uri' =>$callback_url,
                'scope' =>$scope,
            ),null,"&");
            $result["authorize_token_url"] =$this->authorize_token_url."?".$query;
        }

        return $result;
    }
}