<?php


//-------------------------------------
// 
class OAuthAgent_Twitter {
		
	protected $handler;
	protected $consumer_key;
	protected $consumer_secret;
	
	protected $get_access_token_url
			='https://api.twitter.com/oauth/access_token';
	protected $get_request_token_url
			='https://api.twitter.com/oauth/request_token';
	protected $authorize_token_url
			='https://api.twitter.com/oauth/authorize';
	
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
		
		$oauth_secret =& $params["secret_session_ref"];
		$callback_oauth_token =$params["get_vars"]["oauth_token"];
		$callback_oauth_verifier =$params["get_vars"]["oauth_verifier"];
		$callback_url =$params["callback_url"];
		$scope =$params["scope"];
		
		$result =array();
		
		// SecretとCallbackしたパラメータでACEESS-TOKENを取得
		if ($oauth_secret
				&& $callback_oauth_token
				&& $callback_oauth_verifier) {
			
			$response =$this->handler->oauth_request($this->get_access_token_url,array(
				'oauth_consumer_key' =>$this->consumer_key,
				'oauth_consumer_secret' =>$this->consumer_secret,
				'oauth_token' =>$callback_oauth_token,
				'oauth_token_secret' =>$oauth_secret,
				'oauth_verifier' =>$callback_oauth_verifier,
			));
			
			$result["oauth_uid"] =$response["user_id"];
			$result["oauth_token"] =$response["oauth_token"];
			$result["oauth_token_secret"] =$response["oauth_token_secret"];
			$result["oauth_token_expire"] =time()+60*60*24*30;
			$result["profile"] =array(
				"user_id" =>$response["user_id"],
				"user_name" =>$response["screen_name"],
			);
		}
		
		// ACCESS-TOKENがなければ認証リンクを表示
		if ( ! $result["oauth_token"]) {
		
			$response =$this->handler->oauth_request($this->get_request_token_url,array(
				'oauth_consumer_key' =>$this->consumer_key,
				'oauth_consumer_secret' =>$this->consumer_secret,
				'oauth_callback' =>$callback_url,
				'scope' =>$scope,
			));
			
			$oauth_secret =$response['oauth_token_secret'];
			$result["oauth_token_secret"] =$response['oauth_token_secret'];
			$result["authorize_token_url"] =$this->authorize_token_url
					."?oauth_token=".$response['oauth_token'];
		}
		
		return $result;
	}
}