<?php


//-------------------------------------
// 
class OAuthAgent_Facebook {
		
	protected $handler;
	protected $consumer_key;
	protected $consumer_secret;
	
	protected $get_access_token_url
			='https://graph.facebook.com/oauth/access_token';
	protected $authorize_token_url
			='https://www.facebook.com/dialog/oauth';
	protected $graph_url 
			='https://graph.facebook.com/me/';
			
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
			$query =http_build_query(array(
				'client_id' =>$this->consumer_key,
				'client_secret' =>$this->consumer_secret,
				'redirect_uri' =>$callback_url,
				'code' =>$callback_code,
			),null,"&");
			$response =obj("HTTPRequestHandler")->request(
					$this->get_access_token_url."?".$query);
			$response_arr =array();
			parse_str($response["body"],$response_arr);
			
			$result["oauth_token"] =$response_arr["access_token"];
			$result["oauth_token_secret"] ="";
			$result["oauth_token_expire"] =time()+$response_arr["expires"];
			
			// UserID取得			
			$query =http_build_query(array(
				'access_token' =>$result["oauth_token"],
			),null,"&");
			$request_handler =new HTTPRequestHandler;
			$response =$request_handler->request($this->graph_url."?".$query);
			$result_json =json_to_array($response["body"]);
			
			$result["oauth_uid"] =$result_json["id"];
			$result["profile"] =$result_json;
		}
		
		// ACCESS-TOKENがなければ認証リンクを表示
		if ( ! $result["oauth_token"]) {
		
			$query =http_build_query(array(
				'client_id' =>$this->consumer_key,
				'redirect_uri' =>$callback_url,
				'scope' =>$scope,
			),"&");
			$result["authorize_token_url"] =$this->authorize_token_url."?".$query;
		}
		
		return $result;
	}
}