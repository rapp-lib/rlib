<?php
/*

■SPIRAL側事前設定
	
	1. 開発→スパイラルAPI
		★このTOKENとSECRETを呼出設定に使用する

	2. Web→マイエリア発行
		対象のDB、認証方法を設定して発行
		★このマイエリアタイトルはAPI呼び出し設定に使用する
		仮に「auth_001」とする

	3. マイエリア設定→auth_001→カスタムマイページ→追加
		コンテンツは「【キー】=%val:usr:【キー】%」を改行区切りで設定する
		★このキーがget_user_infoの戻り値のキーとなる
		「差替えキーワード表示」を参考にすべてのデータを取り出せるように設定
		仮に「company_id=%val:usr:company_id%」と設定する
	
	4. マイエリア設定→auth_001→カスタムマイページ
		「URL」の欄が「%url/rel:mpg:【マイページID】%」となる
		★このマイページIDはget_my_pageの引数として使う
		仮に「51487」とする
		★一覧から「使用」のチェックをONにして「設定」を押すのを忘れないように


■基本設定

	// [本番環境]SPIRAL APIの接続情報
	registry('Spiral.config',array(
		"token" =>"【TOKEN】",
		"secret" =>"【SECRET】",
		"api_url" =>"https://reg34.smp.ne.jp/api/service",
		"my_area_title" =>"【マイエリアタイトル】",
		"debug" =>false,
	));
	registry('Spiral.my_page_id',42128);
	
	
■ログインとユーザ情報取得

	//-------------------------------------
	// SPLIRAL API ログイン認証
	function spiral_login ($login_id, $login_pw) {
		
		// ログイン処理
		$api =new SpiralApi(registry('Spiral.config'));
		$is_valid =$api->login($login_id, $login_pw);
		
		if ( ! $is_valid) {
			
			return false;
		}
		
		// 認証ユーザ情報の取得
		$my_page_url =$api->get_my_page(registry('Spiral.my_page_id'));
		$user_info =$api->get_user_info($my_page_url);
		
		// ID/PWの保存
		$user_info["login_id"] =$login_id;
		$user_info["login_pw"] =$login_pw;
		
		return $user_info;
	}
*/


//-------------------------------------
// SPIRALのAPI接続機能
class SpiralApi {
	
	protected $config =array();
	protected $last_request =null;
	protected $last_error =null;
	
	//-------------------------------------
	// 初期化
	public function __construct ($config=array()) {
		
		$this->config($config);
	}
	
	//-------------------------------------
	// 設定
	public function config ($config) {
		
		foreach ($config as $k => $v) {
			
			$this->config[$k] =$v;
		}
	}
	
	//-------------------------------------
	// リクエストの記録
	protected function log_request ($request) {
		
		$this->last_request =$request;
	}
	
	//-------------------------------------
	// エラーの記録
	protected function log_error ($error) {
		
		if ($this->config["debug"] && $error["code"] != 121) {
			
			$msg ="<pre style='color:red'>ERROR= ".print_r($error,true)
					."REQUEST= ".print_r($this->last_request,true)."</pre>";
					
			print $msg;
		}
	}
	
	//-------------------------------------
	// エラーチェック
	public function is_error ($res) {
		
		$error =false;
		
		// 通信エラー
		if ($res === false) {
		
			$error =array(
					"type" =>"curl", 
					"message" =>$this->last_error);
			$this->log_error($error);
			
		// APIエラー
		} elseif ($res["message"] != "OK") {
			
			$error =array(
					"type" =>"api", 
					"message" =>$res["message"],
					"code" =>$res["code"]);
			$this->log_error($error);
		}
		
		return $error;
	}
	
	//-------------------------------------
	// API要求
	public function request ($api, $req=array()) {
	
		$req["spiral_api_token"] =$this->config["token"];
		
		// 署名
		$req["passkey"] =time();
		$key =$req["spiral_api_token"]."&".$req["passkey"];
		$req["signature"] =hash_hmac('sha1', $key, $this->config["secret"], false);
		
		// SessionIDの引き継ぎ
		if ($this->config["jsessionid"]) {
			
			$req["jsessionid"] =$this->config["jsessionid"];
		}
			
		// API用のHTTPヘッダ
		$api_headers =array(
			"X-SPIRAL-API: ".$api,
			"Content-Type: application/json; charset=UTF-8",
		);
		
		// リクエストの記録
		$this->log_request(array("api"=>$api, "req" =>$req, "header" =>$api_header));
		
		// curlライブラリを使って送信します。
		$curl = curl_init($this->get_api_url());
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST          , true);
		curl_setopt($curl, CURLOPT_POSTFIELDS    , json_encode($req));
		curl_setopt($curl, CURLOPT_HTTPHEADER    , $api_headers);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER ,false);
		curl_exec($curl);
		
		// エラーがあれば停止
		if (curl_errno($curl)) {
			
			$this->last_error =curl_error($curl);
			return false;
		}
		
		$res_json =curl_multi_getcontent($curl);
		$res =json_decode($res_json,true);
		curl_close($curl);
		
		// SessionIDの引き継ぎ
		if ($res["jsessionid"]) {
			
			$this->config["jsessionid"] =$res["jsessionid"];
		}
		
		return $res;
	}
	
	//-------------------------------------
	// API_URLの取得
	public function get_api_url () {
		
		if ( ! $this->config["api_url"]) {
			
			$this->config["api_url"] ="http://www.pi-pe.co.jp/api/locator";
			$res =$this->request("locator/apiserver/request");
			
			$this->config["api_url"] =$res["location"];
		}
		
		return $this->config["api_url"];
	}
	
	//-------------------------------------
	// ログイン
	public function login ($login_id, $login_pw) {
		
		$res =$this->request("area/login/request",array(
			"my_area_title" =>$this->config["my_area_title"],
			"id" =>$login_id,
			"password" =>$login_pw,
		));
		
		if ($error =$this->is_error($res)) {
			
			// 認証失敗
			if ($error["code"] == 121) {
				
				return false;
				
			// エラー
			} else {
				
				return false;
			}
		}
		
		return true;
	}
	
	//-------------------------------------
	// マイページのURL取得
	public function get_my_page ($my_page_id) {
		
		$res =$this->request("area/mypage/request",array(
			"my_area_title" =>$this->config["my_area_title"],
			"my_page_id" =>$my_page_id,
		));
		
		if ($error =$this->is_error($res)) {
			
			return false;
		}
		
		return $res["url"];
	}
	
	//-------------------------------------
	// ユーザ情報取得
	public function get_user_info ($my_page_url) {
		
		$html =file_get_contents($my_page_url);
		$html =mb_convert_encoding($html,"UTF-8","SJIS-WIN");
		
		$user_info =array();
		
		foreach (explode("\n",$html) as $line) {
			
			list($k,$v) =explode('=',trim($line),2);
			$user_info[$k] =$v;
		}
		
		return $user_info;
	}
	
	//-------------------------------------
	// サンクスメール送信
	public function send_deliver_thanks ($rule_id, $id) {
		
		$res =$this->request("deliver_thanks/send/request",array(
			"rule_id" =>$rule_id,
			"id" =>$id,
		));
		
		if ($error =$this->is_error($res)) {
			
			return false;
		}
		
		return $res;
	}
}
