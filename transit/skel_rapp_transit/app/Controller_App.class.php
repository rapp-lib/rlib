<?php

/**
 * 親Controller
 */
class Controller_App extends Controller_Base 
{
	/**
	 * メール送信
	 */
	protected function send_mail ($options)
	{
		if ($options["template"] && ! $options["template_file"]) {
			$options["template_file"] =registry("Path.webapp_dir")."/mail/".$options["template"].".php";
		}
		if ($options["vars"] && ! $options["template_options"]) {
			$options["template_options"] =$options["vars"];
		}
		/*
		$options["send_mode"] ="smtp";
		$options["send_options"] =array(
				"host" =>"example.com",
				"port" =>"587",
				"auth" =>true,
				"username" =>"info@example.com",
				"password" =>"password",
		);
		*/
		obj("BasicMailer")->send_mail($options);
	}

	/**
	 * act_*前処理
	 */
	public function before_act () 
	{
		parent::before_act();
		
		$this->before_act_force_https();
		$this->before_act_auth();
		$this->before_act_protect_against_csrf();
		//$this->before_act_setup_vif();
		//$this->before_act_config_for_fp();
		
		// リクエスト変換処理
		obj("LayoutRequestArray")->fetch_request_array();
	}
	
	/**
	 * act_*後処理
	 */
	public function after_act () 
	{
		parent::after_act();
	}

	/**
	 * HTTPアクセス制限
	 */
	protected function before_act_force_https () 
	{
		$request_path =registry("Request.request_path");
			
		// HTTPS/HTTPアクセス制限の設定解決
		if ($force_https =registry("Routing.force_https.zone")) {
			
			$is_https =$_SERVER["HTTPS"];
			$is_force_https =in_path($request_path,$force_https);
			$is_safe =in_path($request_path,registry("Routing.force_https.safe_zone"));
				
			// 転送不要 safe_zone
			if ($is_safe) {
				
			// HTTPSへ転送
			} elseif ($is_force_https && ! $is_https) {
				
				$redirect_url =path_to_url($request_path,"https");
				$redirect_url =url($redirect_url,$_GET);
				
				redirect($redirect_url);
				
			// HTTPへ転送
			} elseif ( ! $is_force_https && $is_https) {
				
				$redirect_url =path_to_url($request_path,true);
				$redirect_url =url($redirect_url,$_GET);
				
				redirect($redirect_url);
			}
		}
	}

	/**
	 * 認証処理
	 */
	protected function before_act_auth () 
	{
		$request_path =registry("Request.request_path");
		
		foreach ((array)registry("Auth") as $account => $config) {
			
			$context_name =$config["context_name"];
			$zone =$config["force_login"]["zone"];
			$redirect_to =$config["force_login"]["redirect_to"];
			
			$var_name ="c_".$context_name;
			$class_name =str_camelize($context_name)."Context";
			
			// contextの関連付け
			$this->context($var_name,$var_name,false,array(
				"scope"=>"global",
				"class"=>$class_name,
			));
			
			// model accessorの関連付け
			model(null,$account)->init_accessor(array(
				"account" =>$account,
				"id" =>$this->$var_name->id(),
			));
			
			// ログインしていない場合
			if ( ! $this->$var_name->id()) {
				
				// ログインが必要な場合の転送処理
				if ($zone && in_path($request_path,$zone)) {
					
					redirect($redirect_to,array(
						"redirect_to" =>registry("Request.request_uri")."?".http_build_query($_GET),
					));
				}
			
			// 既にログインしている場合
			} else {
				
				$this->$var_name->refresh();
			}
		}
	}
	
	/**
	 * CSRF対策
	 */
	protected function before_act_protect_against_csrf () 
	{
		$config =registry("Security.csrf");
		$protect_pages =$config["protect_pages"];
		
		$path =registry("Request.request_path");
		$pac_ticket =substr(md5(session_id()),3,6);
		
		if ($protect_pages) {
		
			if (in_path($path,$protect_pages) && $_REQUEST["_pac_ticket"] !== $pac_ticket) {
				
				report_error("PA-CSRF ticket error",array(
					"pac_ticket" =>$pac_ticket,
					"request_pac_ticket" =>$_REQUEST["_pac_ticket"],
				));
			}
			
			add_url_rewrite_rule($protect_pages, "_pac_ticket", $pac_ticket);
		}
	}
	
	/**
	 * Vifリクエスト処理
	 */
	protected function before_act_setup_vif () {
		
		$vif_request =$_SERVER["HTTP_X_VIF_REQUEST"] ? true : false;
		$vif_target_id =$_SERVER["HTTP_X_VIF_TARGET_ID"];
		$vif_history_id =$_SERVER["HTTP_X_VIF_HISTORY_ID"];

		$request_uri =registry("Request.request_uri");
		$vif_request_url =$request_uri.($_GET ? "?".http_build_query($_GET) : "");
		
		registry("Request.vif_request",$vif_request);
		registry("Request.vif_target_id",$vif_target_id);
		registry("Request.vif_history_id",$vif_history_id);
		
		// Vifレスポンスヘッダの発行
		if ($vif_request) {
			
			header("X-Vif-Request-Url:".$vif_request_url);
			header("X-Vif-History-Id:".registry("Request.vif_history_id"));
			header("X-Vif-Target-Id:".registry("Request.vif_target_id"));
		}
		
		// vifアクセス制御
		if (registry("Routing.force_vif.enable")) {
		
			$vif_target_list =registry("Routing.force_vif.target");
			$request_path =registry("Request.request_path");
			
			$vif_target_id_found ="";
			$vif_target_config_found =array();
			
			// request_pathに対応するtarget設定を検索
			foreach ($vif_target_list as $vif_target_id_trial => $vif_target_config) {
				
				if (in_path($request_path, $vif_target_config["area"])) {
					
					$vif_target_id_found =$vif_target_id_trial;
					$vif_target_config_found =$vif_target_config;
					
					if ($vif_target_id == $vif_target_id_found) {
						
						break;
					}
				}
			}
			
			// 制御設定に反するアクセス
			if ($vif_target_id != $vif_target_id_found) {
				
				// Vif内に誤ってフルページURLを読み込んでいる
				if ($vif_request && ! $vif_target_id_found) {
				
					report("Redirct Fullpage by Routing.force_vif",registry("Request"));
					
					header("X-Vif-Response-Code:"."FORCE_FULLPAGE");
					
					shutdown_webapp("async_response");
				}
				
				// 読み込み先ページ誤り、転送先パスの指定がある場合は転送
				if ($vif_target_config_found["path"]) {
				
					$fragment ="vif/".$vif_target_id_found."/2/-".$vif_request_url;
					
					$redirect_url =path_to_url($vif_target_config_found["path"]);
					$redirect_url =url($redirect_url,$_GET,$fragment);
				
					report("Redirct by Routing.force_vif",registry("Request"));
					
					redirect($redirect_url);
				}
				
				// 転送先パスの指定がない、許可設定がない場合はリクエストエラー
				if ($vif_target_id_found != "any") {
					
					report_error("Access Denied by Routing.force_vif",registry("Request"));
				}
			}
		}
	}
	
	/**
	 * ガラケー向け設定変更
	 */
	protected function before_act_config_for_fp () 
	{
		// Docomoガラケー向け設定
		if (preg_match('!Docomo/[12]!',$_SERVER["HTTP_USER_AGENT"])) {
			
			output_rewrite_var(session_name(),session_id());
			registry("Response.content_type", 'application/xhtml+xml');
		}
	}
}
