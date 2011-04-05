<?php
	
	//-------------------------------------
	//
	function start_webapp () {
		
		// Registryのデフォルト値の補完
		$registry_defaultset =array(
			
			// パス設定
			"Path.lib_dir" =>RLIB_ROOT_DIR,
			"Path.tmp_dir" =>"/tmp",
			"Path.document_root_dir" =>realpath($_SERVER["DOCUMENT_ROOT"]),
			"Path.webapp_dir" =>realpath(dirname($_SERVER['SCRIPT_FILENAME'])."/.."),
			"Path.html_dir" =>realpath(dirname($_SERVER['SCRIPT_FILENAME'])),
			
			// エンコーディング設定
			"Config.internal_charset" =>"UTF-8",
			"Config.external_charset" =>"SJIS-WIN",
			
			// Dync機能設定
			"Config.dync_key" =>null,
			"Config.dync_auth_id" =>"e77989ed21758e78331b20e477fc5582",
			"Config.dync_auth_pw" =>"547d913f6ee96d283eb4d50aea20acc1",
			
			// Dtrack機能設定
			"Config.dtrack_key" =>null,
			"Config.dtrack_bind_session" =>false,
			
			// webapp_dir内のinclude_path設定
			"Config.webapp_include_path" =>array(
				"app",
				"app/controller",
				"app/context",
			),
			
			// ライブラリ読み込み設定
			"Config.load_lib" =>array(
				"lib_context",
				"lib_db",
				"lib_smarty",
			),
			
			// レポート出力設定
			"Report.error_reporting" =>E_ALL^E_NOTICE,
		);
		
		foreach ($registry_defaultset as $k => $v) {
			
			if (registry($k) === null) {
				
				registry($k,$v);
			}
		}
		
		// 入出力文字コード変換
		mb_http_output(registry("Config.external_charset"));
		mb_internal_encoding(registry("Config.internal_charset"));
		ob_start("mb_output_handler");
		$_REQUEST =array_merge($_GET,$_POST);
		mb_convert_variables(mb_internal_encoding(),mb_http_output(),$_REQUEST);
		
		// PHPの設定書き換え
		spl_autoload_register("load_class");
		set_error_handler("error_handler",E_ALL^E_NOTICE);
		set_exception_handler('exception_handler');
		session_start();
		session_regenerate_id();
		
		// Dync継続
		if ($dync_key =registry("Config.dync_key")) {
			
			$dync =(array)unserialize($_COOKIE["__dync"]);
			
			// Dync認証
			if (registry("Config.dync_auth_id") && 
					$dync["auth"] != registry("Config.dync_auth_id")) {
					
				$form_html =
						'<form action="'.$_SERVER["REQUEST_URI"]
						.'?'.$_SERVER["QUERY_STRING"].'" method="post">'
						.'ID <input type="text" name="dync_auth_id"/> '
						.'PW <input type="text" name="dync_auth_pw"/> '
						.'<input type="submit" value="Login"/></form>';
						
				if (md5($_REQUEST["dync_auth_id"]) == registry("Config.dync_auth_id")
						&& md5($_REQUEST["dync_auth_pw"]) == registry("Config.dync_auth_pw")) {
					
					$dync["auth"] =registry("Config.dync_auth_id");
				
				} else {
					
					print $form_html;
					exit;
				}
			}
			
			$dync =array_merge($dync,(array)$_REQUEST[$dync_key]);
			setcookie("__dync",serialize($dync),0,"/");
			registry("Config.dync",$dync);
		}
		
		// Dtrack生成
		if ($dtrack_key =registry("Config.dtrack_key")) {
			
			// Dtrack整合チェック
			$dtrack_param =$_REQUEST[$dtrack_key];
			$dtrack_value =$dtrack_param
					? $dtrack_param
					: sprintf('%07d',rand(1,9999999));
			$dtrack_match = ! $dtrack_param || $dtrack_param == $_COOKIE["__dtrack"];
			
			// GETリクエスト運用（リロード等）対策にURLの整合性で再チェック
			$dtrack_url =$_SERVER["REQUEST_URI"].'?'.$_SERVER["QUERY_STRING"];
			
			if ($_GET[$dtrack_key] && ! $dtrack_match 
					&& $dtrack_url == $_COOKIE["__dtrack_url"]) {
				
				$dtrack_value =$dtrack_param =$_COOKIE["__dtrack"];
				$dtrack_match =true;
			}
			
			registry("Config.dtrack_match",$dtrack_match ? 1 : 0);
			
			// 変更前のDtrackのSession情報を予め読み込む
			if ($dtrack_match && registry("Config.dtrack_bind_session")) {
				
				session_name(session_name()."".$dtrack_value);
				session_start();
			}
			
			// Dtrackの変更と登録
			$dtrack_value =sprintf('%07d',rand(1,9999999));
			setcookie("__dtrack",$dtrack_value,0,"/");
			setcookie("__dtrack_url",$dtrack_url,0,"/");
			
			output_rewrite_var(registry("Config.dtrack_key"),$dtrack_value);
			registry("Config.dtrack",$dtrack_value);
			
			// SessionをDtrackに依存して生成させる
			if (registry("Config.dtrack_bind_session")) {
				
				session_name(session_name()."".$dtrack_value);
				session_start();
			}
		}
		
		// include_pathの設定
		foreach ((array)registry("Config.webapp_include_path") as $k => $v) {
			
			add_include_path(registry("Path.webapp_dir")."/".$v);
		}
		
		// ライブラリの読み込み
		foreach ((array)registry("Config.load_lib") as $k => $v) {
			
			load_lib($v);
		}
		
		// WebappBuild機能
		if (get_webapp_dync("webapp_build") && $_REQUEST["exec"]) {
		
			obj("WebappBuilder")->webapp_build();
			exit;
		}
	}
	
	//-------------------------------------
	// 出力と同時に終了
	function clean_output_shutdown ($output) {
		
		registry("Report.buffer_enable");
		ob_end_clean();
		print $output;
		
		shutdown_webapp("clean_output");
	}
	
	//-------------------------------------
	// URL書き換え機能
	function output_rewrite_var ($name=null, $value=null) {
	
		$output_rewrite_var =& ref_globals("output_rewrite_var");
		$result =array_registry($output_rewrite_var,$name,$value);
		
		if ($value !== null) {
			
			output_add_rewrite_var($name,$value);
		}
		
		if (ini_get("session.use_trans_sid")) {
		
			if ($name === null) {
			
				$result[session_name()] =session_id();
				
			} elseif ($name === session_name()) {
			
				$result =session_id();
			}
		}
		
		return $result;
	}
	
	//-------------------------------------
	//
	function shutdown_webapp ($cause=null, $options=array()) {
		
		$funcs =& ref_globals('shutdown_webapp_function');
		
		foreach ((array)$funcs as $func) {
			
			call_user_func_array($func,array(
				$cause,
				$options
			));
		}
		
		exit;
	}
	
	//-------------------------------------
	//
	function register_shutdown_webapp_function ($func) {
		
		$funcs =& ref_globals('shutdown_webapp_function');
		$funcs[] =$func;
	}
	
	//-------------------------------------
	//
	function elapse ($event=null,$stop=false) {
		
		static $time =array();
		
		if ( ! $event) {
			
			return (array)$time["interval"];
		}
		
		if ($stop && $time["start"][$event]) {
		
			$interval =microtime(true) - $time["start"][$event];
			$time["interval"][$event] =round($interval*1000)."ms";
			
		} elseif ( ! $stop) {
		
			$time["start"][$event] =microtime(true);
		}
		
		return array();
	}
	
	//-------------------------------------
	//
	function redirect ($url) {
		
		if (preg_match('!^page:(.*)$!',$url,$match)) {
			
			if ($tmp_url =page_to_url($match[1])) {
			
				$url =$tmp_url;
			
			} else {
			
				report_error("Redirect page is-not routed.",array(
					"page" =>$match[1],
				));
			}
		}
		
		if (get_webapp_dync("report")) {
			
			print tag("a",array("href"=>$url),
					'<div style="padding:20px;background-color:#f8f8f8;border:solid 1px #aaaaaa;">'
					.'Redirect ... '.url($url,output_rewrite_var()).'</div>');
			
		} else {
		
			header("Location: ".url($url,output_rewrite_var()));
		}
		
		shutdown_webapp("redirect");
	}
	
	//-------------------------------------
	// 稼働状態の確認
	function get_webapp_dync ($flg="report") {
		
		if (strpos($_SERVER["HTTP_USER_AGENT"],"DYNC_AVATAR") !== false) {
			
			// DYNC
			return true;
			
		} else {
		
			if ($flg && registry("Config.dync.".$flg)) {
				
				// 仮想DYNC
				return true;
				
			} else {
				
				// 稼働中
				return false;
			}
		}
	}
	
	//-------------------------------------
	// ラベルを得る
	function label ($name) {
		
		return (string)registry("Label.".$name);
	}
