<?php

//-------------------------------------
// Controller基本クラス
class Controller_App extends Controller_Base {

	//-------------------------------------
	// act_*前処理
	public function before_act () {
	
		parent::before_act();
		
		$this->before_act_config_for_fp();
		$this->before_act_force_https();
		$this->before_act_setup_vif();
		
		// リクエスト変換処理
		obj("LayoutRequestArray")->fetch_request_array();
		
		// 認証設定
		// $this->context("c_admin_auth","c_admin_auth",false,"AdminAuthContext");
		// $this->c_admin_auth->check_auth();
	}
	
	//-------------------------------------
	// act_*後処理
	public function after_act () {
	
		parent::after_act();
	}
	
	//-------------------------------------
	// ガラケー向け設定変更
	protected function before_act_config_for_fp () {
		
		// Docomoガラケー向け設定
		if (preg_match('!Docomo/[12]!',$_SERVER["HTTP_USER_AGENT"])) {
			
			output_rewrite_var(session_name(),session_id());
			registry("Response.content_type", 'application/xhtml+xml');
		}
	}
	
	//-------------------------------------
	// HTTPアクセス制限
	protected function before_act_force_https () {
		
		$request_path =registry("Request.request_path");
			
		// HTTPS/HTTPアクセス制限の設定解決
		if ($force_https =registry("Routing.force_https.area")) {
			
			$is_https =$_SERVER["HTTPS"];
			$is_force_https =in_path($request_path,$force_https);
				
			// HTTPSへ転送
			if ($is_force_https && ! $is_https) {
				
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
	
	//-------------------------------------
	// Vifリクエスト処理
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
}