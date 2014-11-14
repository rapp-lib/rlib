<?php


//-------------------------------------
// HTTPリクエストクラス
class HTTPRequestHandler {
	
	//-------------------------------------
	// HTTPリクエスト送信
	public function request ($url, $params=array()) {
		
		return $this->request_http_request2($url,$params);
		//return $this->request_curl_DEPRECATED($url,$params);
	}
	
	//-------------------------------------
	// Pear_HTTP_Request2によるHTTPリクエスト送信
	public function request_http_request2 ($url, $params=array()) {
		
		require_once "HTTP/Request2.php";
		
		try {
		
		    $handle = new HTTP_Request2();
			
			$handle->setUrl($url);
			
			$handle->setMethod(isset($params["post"])
					? HTTP_Request2::METHOD_POST
					: HTTP_Request2::METHOD_GET);
			
			$handle->setConfig('ssl_verify_peer', false);
			$handle->setConfig('protocol_version', '1.1');
			$handle->setConfig('connect_timeout', '300');
			$handle->setConfig('timeout', '300');
			$handle->setConfig('follow_redirects', true);
			$handle->setConfig('max_redirects', 10);
			
			// Curlで送信
			if ($params["adapter"] == "curl") {
			
				require_once 'HTTP/Request2/Adapter/Curl.php';
				$handle->setAdapter(new HTTP_Request2_Adapter_Curl());
			}
			
			// 設定変更
			foreach ((array)$params["config"] as $k => $v) {
			
				$handle->setConfig($k,$v);
			}
			
			// POST設定
			foreach ((array)$params["post"] as $k => $v) {
				
				if (is_numeric($k)) {
					
					$handle->addPostParameter($v);
					
				} else {
				
					$handle->addPostParameter($k,$v);
				}
			}
			
			// Header追加
			foreach ((array)$params["headers"] as $k => $v) {
			
				$handle->setHeader($k,$v);
			}
			
			// ベーシック認証のusernameとpasswordの指定
			if ($params["basic_auth"]) {
				
				$handle->setAuth(
						$params["basic_auth"]["username"],
						$params["basic_auth"]["password"],
						HTTP_Request2::AUTH_BASIC);
			}
		
			$response =$handle->send();
		
			return array(
				"body" =>$response->getBody(),
				"result" =>preg_match('!^2..$!',$response->getStatus()),
				"code" =>$response->getStatus(),
				"headers" =>$response->getHeader(),
			);
			
		} catch (Exception $e) {
			
			return array(
				"error" =>$e,
			);
		}
	}
	
	//-------------------------------------
	// (非推奨 130612 Y.Toyosawa)CURLによるHTTPリクエスト送信
	public function request_curl_DEPRECATED ($url, $params=array()) {
		
		$handle =new HTTPRequest_Curl($url);
		
		// 送信するリクエストヘッダの指定
		if ($params["headers"]) {
		
			$handle->set_request_headers($params["headers"]);
		}
		
		// ポートの指定
		if ($params["port"]) {
		
			$handle->set_option(CURLOPT_PORT,$params["port"]);
		}
		
		// POSTメソッドで送信する値の指定
		if (isset($params["post"])) {
		
			$handle->set_post_values($params["post"]);
		}
		
		// メソッドの指定
		if ($params["method"]) {
		
			$handle->set_method($params["method"]);
		}
		
		// ベーシック認証のusernameとpasswordの指定
		if ($params["basic_auth"]) {
		
			$handle->set_basic_auth(
					$params["basic_auth"]["username"],
					$params["basic_auth"]["password"]);
		}
		
		// Cookieを管理するファイルの指定
		if ($params["cookie_file"]) {
		
			$handle->set_cookie_file($params["cookie_file"]);
		}
		
		// 通信ログの記録
		if ($params["log"]) {
		
			$handle->set_log($params["log"]);
		}
		
		$body =$handle->send_request();
		
		return array(
			"body" =>$body,
			"result" =>$handle->is_succeeded(),
			"code" =>$handle->get_response_code(),
			"error" =>$handle->get_error(),
			"headers" =>$handle->get_response_headers(),
		);
	}
}

//-------------------------------------
// CURLリソース操作支援クラス
class HTTPRequest_Curl {
	
	protected $options;
	protected $error;
	protected $errno;
	protected $response_code;
	protected $response_headers;
	
	//-------------------------------------
	// コンストラクタ
	public function __construct (
			$url="", 
			$options=array()) {
		
		$this->init($url,$options);
	}
	
	//-------------------------------------
	// 初期化
	public function init (
			$url="", 
			array $options=array()) {
		
		$this->options =$options;
		$this->set_option(CURLOPT_URL,$url);
		$this->set_option(CURLOPT_HEADER,true);
		$this->set_option(CURLOPT_RETURNTRANSFER,true);
		$this->set_option(CURLOPT_MAXREDIRS,10);
		$this->set_option(CURLOPT_FOLLOWLOCATION,true);
		$this->set_option(CURLOPT_SSLVERSION,3);
		$this->set_option(CURLOPT_SSL_VERIFYPEER,false);
		$this->set_option(CURLOPT_SSL_VERIFYHOST,false);

		// 100-continue対策
		$this->options[CURLOPT_HTTPHEADER][] ='Expect:';
	}
	
	//-------------------------------------
	// 最後に発行した要求が成功かどうか
	public function is_succeeded () {
		
		return ($this->response_code >= 200 
				&& $this->response_code < 300
				&& ! $this->errno);
	}
	
	//-------------------------------------
	// 最後に発行した要求に対する応答コード
	public function get_response_code () {
		
		return $this->response_code;
	}
	
	//-------------------------------------
	// 最後に発行した要求に対する応答ヘッダ
	public function get_response_headers () {
		
		return $this->response_headers;
	}
	
	//-------------------------------------
	// 最後に発行した要求に関するエラー情報
	public function get_error () {
		
		return $this->error;
	}
	
	//-------------------------------------
	// CURLオプションの取得
	public function get_option ($key) {
		
		return $this->options[$key];
	}
	
	//-------------------------------------
	// CURLオプションの一括取得
	public function get_options () {
		
		return $this->options;
	}
	
	//-------------------------------------
	// CURLオプションの指定
	public function set_option ($key, $value) {
	
		$this->options[$key] =$value;
	}
	
	//-------------------------------------
	// CURLオプションの一括指定
	public function set_options (array $options) {
		
		foreach ($options as $key =>$value) {
		
			$this->set_option($key,$value);
		}
	}
	
	//-------------------------------------
	// URLの指定
	public function set_url ($url) {
		
		$this->set_option(CURLOPT_URL,$url);
	}
	
	//-------------------------------------
	// Cookieを管理するファイルの指定
	public function set_cookie_file ($cookiefile) {
	
		$this->set_option(CURLOPT_COOKIEFILE,$cookiefile);
		$this->set_option(CURLOPT_COOKIEJAR,$cookiefile);
	}
	
	//-------------------------------------
	// メソッドの指定
	public function set_method ($method) {
	
		$this->set_option(CURLOPT_CUSTOMREQUEST,$method);
	}
	
	//-------------------------------------
	// POSTメソッドで送信する値の指定
	public function set_post_values ($post_field) {
		
		if (is_array($post_field)) {
			
			$post_field =http_build_query($post_field);
		}
		
		$this->set_option(CURLOPT_POST,true);
		$this->set_option(CURLOPT_POSTFIELDS,$post_field);
	}
	
	//-------------------------------------
	// ベーシック認証のusernameとpasswordの指定
	public function set_basic_auth ($username,$password) {
		
		$this->set_option(CURLOPT_USERPWD, $username.":".$password);
	}
	
	//-------------------------------------
	// 送信するリクエストヘッダの指定
	public function set_request_headers (array $request_headers) {
		
		foreach ($request_headers as $request_header) {
		
			$this->options[CURLOPT_HTTPHEADER][] =$request_header;
		}
	}
	
	//-------------------------------------
	// 通信ログの記録
	public function set_log ($log_filename) {
	
		// 詳細な情報を出力する
		$this->set_option(CURLOPT_VERBOSE,true);
		
		// STDERR の代わりにエラーを出力するファイルポインタ
		$this->set_option(CURLOPT_STDERR,fopen($log_filename,'a'));
	}
	
	//-------------------------------------
	// リクエストの発行
	public function send_request () {
		
		$curl =curl_init();
		curl_setopt_array($curl,$this->get_options());
		$response_data =curl_exec($curl);		
		$maxredirs =$this->options[CURLOPT_FOLLOWLOCATION]
				? $this->options[CURLOPT_MAXREDIRS]
				: 0;
		list($this->response_headers,$body) 
				=self::parse_response($response_data,$maxredirs);
		$this->response_code =curl_getinfo($curl,CURLINFO_HTTP_CODE);
		$this->errno =curl_errno($curl);
		$this->error =curl_error($curl);
		
		curl_close($curl);
		
		if ($this->response_code < 200 
				|| $this->response_code >= 300) {
		
			$this->error ='Response code "'.
					$this->response_code.'" returned.';
		}
		
		return $body;
	}
	
	//-------------------------------------
	// 応答の解析
	protected static function parse_response (
			$response_data, 
			$maxredirs=0) {
		
		list($header_lines_str, $body) =preg_split("!\r?\n\r?\n!",$response_data,2);
		$header_lines =preg_split("!\r?\n!",$header_lines_str);
		$http_desc =array_shift($header_lines);
		
		foreach ($header_lines as $header_line) {
			
			list($key, $value) =explode(":",$header_line,2);
			$headers[strtolower(trim($key))] =trim($value);
		}
		
		if ($maxredirs && $headers["location"]) {
			
			return self::parse_response($body,$maxredirs-1);
		}
		
		return array($headers,$body);
	}
	
	//-------------------------------------
	// 複合CURLリクエスト送信
	public static function concurrent_request (array $curl_handlers) {
		
		$curl_multi =curl_multi_init();
		$curls =array();
		$options =array();
		
		foreach ($curl_handlers as $key =>$curl_handler) {
			
			if ($curl_handler 
					&& $curl_handler instanceof HTTPRequestCurl) {
			
				$curls[$key] =curl_init();
				$options[$key] =$curl_handler->get_options();
				curl_setopt_array($curls[$key],$options[$key]);
				curl_multi_add_handle($curl_multi,$curls[$key]);
			}
		}
		
		$running_count =null;
		
		do {
		
			curl_multi_exec($curl_multi,$running_count);
			
		} while ($running_count>0);
		
		$results =array();
		
		foreach ($curls as $key =>$curl) {
			
			$response_data =curl_multi_getcontent($curl);
			$maxredirs =$options[$key][CURLOPT_FOLLOWLOCATION]
					? $options[$key][CURLOPT_MAXREDIRS]
					: 0;
			list($response_headers,$body) 
					=self::parse_response($response_data,$maxredirs);
			$response_code =curl_getinfo($curl,CURLINFO_HTTP_CODE);
			$errno =curl_errno($curl);
			$error =curl_error($curl);
			curl_multi_remove_handle($curl_multi,$curl);
				
			if ($errno) {
			
				$body =null;
		
			} elseif ($response_code < 200 || $response_code >= 300) {
		
				$error ='Response code "'.$response_code.'" returned.';
				$body =null;
			}
			
			$results[$key] =array(
				"body" =>$body,
				"errno" =>$errno,
				"error" =>$error,
				"response_code" =>$response_code,
				"response_headers" =>$response_headers
			);
		}
		
		curl_multi_close($curl_multi);
		
		return $results;
	}
}