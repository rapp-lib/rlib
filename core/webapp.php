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
			
			// セッション設定
			"Config.session_start_function" =>"std_session_start",
			
			// webapp_dir内のinclude_path設定
			"Config.webapp_include_path" =>array(
				"app",
				"app/include",
				"app/controller",
				"app/context",
				"app/list",
				"app/model",
				"app/widget",
			),
			
			// ライブラリ読み込み設定
			"Config.load_lib" =>array(
				"lib_context",
				"lib_db",
				"lib_smarty",
			),
			
			// レポート出力設定
			"Report.error_reporting" =>E_ALL&~E_NOTICE,
		);
		
		foreach ($registry_defaultset as $k => $v) {
			
			if (registry($k) === null) {
				
				registry($k,$v);
			}
		}
		
		// HTTPパラメータ構築
		$_REQUEST =array_merge($_GET,$_POST);
		
		// 入出力文字コード変換
		mb_http_output(registry("Config.external_charset"));
		mb_internal_encoding(registry("Config.internal_charset"));
		ob_start("mb_output_handler_impl");
		mb_convert_variables(mb_internal_encoding(),mb_http_output(),$_REQUEST);
		sanitize_request_variables($_REQUEST);
		
		// PHPの設定書き換え
		spl_autoload_register("load_class");
		set_error_handler("std_error_handler",E_ALL);
		set_exception_handler('std_exception_handler');
		
		// session_start
		call_user_func(registry("Config.session_start_function"));
		
		// Dync機能の有効化
		start_dync();
		
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
	// std_session_start
	function std_session_start () {
		
		// セッションの開始
		ini_set("session.cookie_lifetime",0);
		ini_set("session.cookie_httponly",true);
		ini_set("session.cookie_secure",$_SERVER['HTTPS']);
		
		session_cache_limiter('nocache');
		session_start();
	}
	
	//-------------------------------------
	// start_dync
	function start_dync () {
		
		// Dync継続
		if ($dync_key =registry("Config.dync_key")) {
			
			$dync =(array)unserialize($_COOKIE["__dync"]);
			
			// Dync認証
			if ($_REQUEST[$dync_key]
					&& registry("Config.dync_auth_id")
					&& $dync["auth"] != registry("Config.dync_auth_id")) {
					
				$form_html =
						'<span onclick="document.getElementById(\'F\')'
						.'.style.visibility=\'visible\';" style="color:#FFFFFF">'
						.'.</span><form action="'.$_SERVER["REQUEST_URI"]
						.'?'.$_SERVER["QUERY_STRING"].'" method="post"'
						.' style="visibility:hidden;" id="F">'
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
			
			if ($dync["report"]) {
				
				ini_set("display_errors",true);
				ini_set("error_reporting",registry("Report.error_reporting"));
			}
		}
	}
	
	//-------------------------------------
	// ob_filter
	function mb_output_handler_impl ($html) {
	
		$html =mb_convert_encoding($html,mb_http_output(),mb_internal_encoding());
				
		return $html;
	}
	
	//-------------------------------------
	// リクエスト値をサニタイズ
	function sanitize_request_variables ( & $input) {
		
		if (is_array($input)) {
		
			foreach ($input as $k => $v) {
			
				sanitize_request_variables($input[$k]);
			}
			
		} else {
		
			$input =str_replace(array("&","<",">"),array("&amp;","&lt;","&gt;"),$input);
		}
	}
	
	//-------------------------------------
	// 出力と同時に終了
	function clean_output_shutdown ($output) {
		
		registry("Report.buffer_enable",true);
		
		while (ob_get_level()) {
			
			ob_end_clean();
		}
		
		// download
		if (is_array($output) && $output["download"]) {
		
			$download_filename =$output["download"];
			
			if ( ! $download_filename) {
				
				if (is_array($output) && $output["file"]) {
					
					$download_filename =basename($output["file"]);
					
				} else {
				
					$download_filename ="noname";
				}
			}
					
			header("Content-Disposition: attachment; filename=".$download_filename);
		}
		
		// content_type
		if (is_array($output) && $output["content_type"]) {
			
			header("Content-Type: ".$output["content_type"]);
		
		} elseif (is_array($output) && $output["download"]) {
		
			header("Content-Type: application/octet-stream");
			
		} elseif (is_array($output) && $output["file"]) {
		
			$image_type =@exif_imagetype($cache_file);
			$mime_type =@image_type_to_mime_type($image_type);
			$mime_type =$mime_type
					? $mime_type
					: "application/octet-stream";
			header("Content-Type: ".$mime_type);
		}
		
		// output
		if (is_string($output)) {
			
			echo $output;
		
		// data
		} elseif (is_array($output) && $output["data"]) {
			
			echo $output["data"];
		
		// file
		} elseif (is_array($output) && $output["file"]) {
			
			readfile($output["file"]);
		}
		
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
		
		return $result;
	}
	
	//-------------------------------------
	//
	function shutdown_webapp ($cause=null, $options=array()) {
		
		$funcs =& ref_globals('shutdown_webapp_function');
		
		foreach (array_reverse((array)$funcs) as $func) {
			
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
	function redirect ($url, $params=array()) {
		
		if (preg_match('!^page:(.*)$!',$url,$match)) {
			
			if ($tmp_url =page_to_url($match[1])) {
			
				$url =$tmp_url;
			
			} else {
			
				report_error("Redirect page is-not routed.",array(
					"page" =>$match[1],
				));
			}
		}
		
		$url =url($url,array_merge((array)$params,(array)output_rewrite_var()));
		
		if (get_webapp_dync("report")) {
			
			print tag("a",array("href"=>$url),
					'<div style="padding:20px;'
					.'background-color:#f8f8f8;border:solid 1px #aaaaaa;">'
					.'Redirect ... '.$url.'</div>');
			
		} else {
		
			header("Location: ".$url);
		}
		
		shutdown_webapp("redirect");
	}
	
	//-------------------------------------
	// 稼働状態の確認
	function get_webapp_dync ($flg="report") {
		
		return $flg && registry("Config.dync.".$flg);
	}
	
	//-------------------------------------
	// ラベルを得る
	function label ($name) {
		
		return (string)registry("Label.".$name);
	}
	
	//-------------------------------------
	// UserAgentの判定
	function check_user_agent ($detail=0, $user_agent_string=null) {
		
		/*
			[detail arg]:   0  / 1
			iPhone or iPod: sp / iphone
			iPad:           sp / ipad
			Android Phone:  sp / android_phone
			Android Tablet: sp / android_tab
			Softbank:       mb / softbank
			DoCoMo:         mb / docomo
			AU:             mb / au
			Others:         pc / pc
		*/
		
		if ($user_agent_string === null) {
			
			$user_agent_string =$_SERVER["HTTP_USER_AGENT"];
		}
		
		$ua_list =array(
			'iphone'        =>array('!iPhone|iPod!',                     'sp'),
			'ipad'          =>array('!iPad!',                            'sp'),
			'android_phone' =>array('!Android.*?Mobile!',                'sp'),
			'android_tab'   =>array('!Android!',                         'sp'),
			'softbank'      =>array('!J-PHONE|Vodafone|MOT-|SoftBank!i', 'mb'),
			'docomo'        =>array('!DoCoMo!i',                         'mb'),
			'au'            =>array('!UP\.Browser|KDDI!i',               'mb'),
		);
		
		foreach ($ua_list as $k => $v) {
			
			if (preg_match($v[0],$user_agent_string)) {
				
				if ($detail == 0) {
				
					return $v[1];
				}
				
				return $k;
			}
		}
		
		return "pc";
	}