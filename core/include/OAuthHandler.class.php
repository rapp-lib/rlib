<?php
/*

SAMPLE CODE:
	
	// ACCESS-TOKENを取得する
	
	require_once("/var/www/vhosts/dev.sharingseed.info/rlib.git.head/core.php");
	registry("Config.dync_key","_");
	start_webapp();
	
	$get_access_token_url= 'https://www.google.com/accounts/OAuthGetAccessToken';
	$get_request_token_url= 'https://www.google.com/accounts/OAuthGetRequestToken';
	$authorize_token_url= 'https://www.google.com/accounts/OAuthAuthorizeToken';
	$consumer_key='www.sharingseed.co.jp';
	$consumer_secret='UB3YgOSS1rIz8yNIc1OHZOvW';
	$callback_url='http://test.dev.sharingseed.info/Social/oauth_sample.php';
	$scope ='https://www.google.com/m8/feeds';
	
	$session =& $_SESSION["gmail_oauth_test01"];
	$callback_oauth_token =$_GET['oauth_token'];
	$callback_oauth_verifier =$_GET['oauth_verifier'];
	
	// SecretとCallbackしたパラメータでACEESS-TOKENを取得
	if ($session['oauth_token_secret'] 
			&& $callback_oauth_token
			&& $callback_oauth_verifier) {
	
		$access_token =obj("OAuthHandler")->oauth_request($get_access_token_url,array(
			'oauth_consumer_key' =>$consumer_key,
			'oauth_consumer_secret' =>$consumer_secret,
			'oauth_token' =>$callback_oauth_token,
			'oauth_token_secret' =>$session['oauth_token_secret'],
			'oauth_verifier' =>$callback_oauth_verifier,
		));
		
		$session['access_token'] =$access_token['oauth_token'];
	}
	
	// ACCESS-TOKENがなければ認証リンクを表示
	if ( ! $session['access_token']) {
	
		$response =obj("OAuthHandler")->oauth_request($get_request_token_url,array(
			'oauth_consumer_key' =>$consumer_key,
			'oauth_consumer_secret' =>$consumer_secret,
			'oauth_callback' =>$callback_url,
			'scope' =>$scope,
		));
		
		$session['oauth_token_secret'] =$response['oauth_token_secret'];
		$authorize_token_url .="?oauth_token=".$response['oauth_token'];
		
		print '<a href="'.$authorize_token_url.'">[OAuth by Gmail]</a>';
		
	} else {
	
		print "ACCESS_TOKEN: ".$session['access_token'];
	}
*/

//-------------------------------------
// 
class OAuthHandler {
		
	//-------------------------------------
	// 
	public function get_agent ($service, $params=array()) {
		
		$class_name ="OAuthAgent_".$service;		
		
		require_once(dirname(__FILE__)."/OAuthHandler/".$class_name.".class.php");
		
		return new $class_name($this, $params);
	}
		
	//-------------------------------------
	// 
	public function oauth_request (
			$url,
			$params,
			$use_post=false,
			$response_format="query_string") {
		
		$params['oauth_version'] = '1.0';
        $params['oauth_nonce'] = md5(mt_rand());
        $params['oauth_timestamp'] = time();
		$params["oauth_signature_method"] = "HMAC-SHA1";
		
		$oauth_consumer_secret =$params['oauth_consumer_secret'];
		unset($params['oauth_consumer_secret']);
		
		$oauth_token_secret =$params['oauth_token_secret'];
		unset($params['oauth_token_secret']);
		
		// compute signature and add it to the params list
		$params['oauth_signature'] =$this->oauth_compute_hmac_sig(
				($use_post ? 'POST' : 'GET'), 
				$url, 
				$params,
				$oauth_consumer_secret, 
				$oauth_token_secret);


		// Pass OAuth credentials in a separate header or in the query string
		$query_parameter_string =$this->oauth_http_build_query($params,true);
		$headers =array("Authorization"=>$this->build_oauth_header($params));
		
		$response =array();
		
		// POST or GET the request
		if ($use_post) {
		
			$request_handler =new HTTPRequestHandler;
			$response =$request_handler->request($url,array(
				"post" =>$query_parameter_string,
				"headers" =>$headers,
			));
			
		} else {
		
			$url .='?'.$query_parameter_string;

			$request_handler =new HTTPRequestHandler;
			$response =$request_handler->request($url,array(
				"headers" =>$headers,
			));
		}
		
		if ( ! $response["result"]) {
			
			report_warning("OAuth Request failur",array(
				"url" =>$url,
				"method" =>$use_post ? "POST" : "GET",
				"headers" =>$headers,
				"params" =>$query_parameter_string,
				"response" =>$response,
			));
			
			return null;
		}
		
		// extract successful response
		$response_body =$response["body"];
		
		if ($response_format == "query_string") {
		
			$response_body =$this->oauth_parse_str($response_body);
		}
		
		if ($response_format == "json") {
		
			$response_body =json_to_array($response_body);
		}
		
		return $response_body;
	}
	
	//-------------------------------------
	// 
	protected function oauth_http_build_query(
			$params, 
			$excludeOauthParams=false) {

		$query_string = '';
		if (!empty($params)) {

			// rfc3986 encode both keys and values
			$keys = $this->rfc3986_encode(array_keys($params));
			$values = $this->rfc3986_encode(array_values($params));
			$params = array_combine($keys, $values);

			// Parameters are sorted by name, using lexicographical byte value ordering.
			// http://oauth.net/core/1.0/#rfc.section.9.1.1
			uksort($params, 'strcmp');

			// Turn params array into an array of "key=value" strings
			$kvpairs = array();
			
			foreach ($params as $k => $v) {
				
				if ($excludeOauthParams && substr($k, 0, 5) == 'oauth') {
					
					continue;
				}
				
				if (is_array($v)) {
					
					// If two or more parameters share the same name,
					// they are sorted by their value. OAuth Spec: 9.1.1 (1)
					natsort($v);
					
					foreach ($v as $value_for_same_key) {
						
						array_push($kvpairs, ($k . '=' . $value_for_same_key));
					}
					
				} else {
					
					// For each parameter, the name is separated from the corresponding
					// value by an '=' character (ASCII code 61). OAuth Spec: 9.1.1 (2)
					array_push($kvpairs, ($k . '=' . $v));
				}
			}

			// Each name-value pair is separated by an '&' character, ASCII code 38.
			// OAuth Spec: 9.1.1 (2)
			$query_string = implode('&', $kvpairs);
		}
		
		return $query_string;
	}
	
	//-------------------------------------
	// 
	protected function oauth_parse_str($query_string) {
	
		$query_array = array();

		if (isset($query_string)) {

			// Separate single string into an array of "key=value" strings
			$kvpairs = explode('&', $query_string);

			// Separate each "key=value" string into an array[key] = value
			foreach ($kvpairs as $pair) {
			
				list($k, $v) = explode('=', $pair, 2);

				// Handle the case where multiple values map to the same key
				// by pulling those values into an array themselves
				if (isset($query_array[$k])) {
					
					// If the existing value is a scalar, turn it into an array
					if (is_scalar($query_array[$k])) {
						
						$query_array[$k] = array($query_array[$k]);
					}
					
					array_push($query_array[$k], $v);
					
				} else {
					
					$query_array[$k] = urldecode($v);
				}
			}
		}

		return $query_array;
	}
	
	//-------------------------------------
	// 
	protected function build_oauth_header($params, $realm='') {
	
		$headers =array(); 
		
		uksort($params, 'strcmp');
		
		foreach ($params as $k => $v) {
			
			if (substr($k, 0, 5) == 'oauth') {
			
				$headers[] =$this->rfc3986_encode($k)
						.'="'.$this->rfc3986_encode($v).'"';
			}
		}
		
		return 'OAuth '.implode($headers,', ');
	}
	
	//-------------------------------------
	// 
	protected function oauth_compute_plaintext_sig($consumer_secret, $token_secret) {
	
		return ($consumer_secret . '&' . $token_secret);
	}
	
	//-------------------------------------
	// 
	protected function oauth_compute_hmac_sig(
			$http_method, 
			$url, 
			$params, 
			$consumer_secret, 
			$token_secret) {

		$base_string = $this->signature_base_string($http_method, $url, $params);
		$signature_key = $this->rfc3986_encode($consumer_secret) 
				. '&' . $this->rfc3986_encode($token_secret);
		$sig = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));
		
		if ($this->debug) {
			
			logit("oauth_compute_hmac_sig:DBG:sig:$sig");
		}
		
		return $sig;
	}
	
	//-------------------------------------
	// 
	protected function normalize_url($url) {
		
		$parts = parse_url($url);

		$scheme = $parts['scheme'];
		$host = $parts['host'];
		$port = $parts['port'];
		$path = $parts['path'];

		if (!$port) {
			
			$port = ($scheme == 'https') ? '443' : '80';
		}
		
		if (($scheme == 'https' && $port != '443')
				|| ($scheme == 'http' && $port != '80')) {
			
			$host = "$host:$port";
		}

		return "$scheme://$host$path";
	}
	
	//-------------------------------------
	// 
	protected function signature_base_string($http_method, $url, $params) {
		
		// Decompose and pull query params out of the url
		$query_str = parse_url($url, PHP_URL_QUERY);
		if ($query_str) {
			
			$parsed_query = $this->oauth_parse_str($query_str);
			
			// merge params from the url with params array from caller
			$params = array_merge($params, $parsed_query);
		}

		// Remove oauth_signature from params array if present
		if (isset($params['oauth_signature'])) {
			
			unset($params['oauth_signature']);
		}

		// Create the signature base string. Yes, the $params are double encoded.
		$base_string = $this->rfc3986_encode(strtoupper($http_method)) . '&' .
				$this->rfc3986_encode($this->normalize_url($url)) . '&' .
				$this->rfc3986_encode($this->oauth_http_build_query($params));

		return $base_string;
	}
	
	//-------------------------------------
	// 
	protected function rfc3986_encode($raw_input){

		if (is_array($raw_input)) {
		
			//return array_map($this->rfc3986_encode, $raw_input);
			return array_map(array($this, 'rfc3986_encode'), $raw_input);
			// return $this->rfc3986_encode($raw_input);
			
		} else if (is_scalar($raw_input)) {
			
			return str_replace('%7E', '~', rawurlencode($raw_input));
		} else {
			
			return '';
		}
	}
	
	//-------------------------------------
	// 
	protected function rfc3986_decode($raw_input) {
		
		return rawurldecode($raw_input);
	}
}