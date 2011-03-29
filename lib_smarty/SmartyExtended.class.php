<?php
	
	require_once(RLIB_ROOT_DIR."/core/smarty/Smarty.class.php");

//-------------------------------------
// 
class SmartyExtended extends Smarty {
	
	//-------------------------------------
	// 初期化
	public function __construct () {
		
		$cache_dir =registry("Path.tmp_dir").'/smarty_cache/';
		
		$this->left_delimiter ='{{';
		$this->right_delimiter ='}}';
		$this->default_template_handler_func
				=array($this,"default_template_handler");
		$this->plugins_dir[] ="modules/smarty_plugin/";
		$this->cache_dir =$cache_dir;
		$this->compile_dir =$cache_dir;
		
		if ( ! file_exists($cache_dir) 
				&& is_writable(dirname($cache_dir))) {
			
			mkdir($cache_dir,0777);
		}
	}

	//-------------------------------------
	// 無効なメソッド呼び出し
	public function __call ($method, $args) {
	
		report_warning(get_class($this).'::'.$method.' is-not callable. ');
	}
	
	//-------------------------------------
	// registered Smarty::$default_template_handler_func
	public function default_template_handler (
			$resource_type, 
			$resource_name, 
			&$template_source, 
			&$template_timestamp, 
			&$smarty) {
		
		if ($resource_type == 'page') {
			
			$file =page_to_file($resource_name);
			
			if ($file) {
			
				$resource_name =$file;
				
			} else {
			
				report_error("Smarty Template page is-not routed.",array(
					"page" =>$resource_name,
				));
			}
		}
		
		if ($resource_type == 'path') {
			
			$file =registry("Path.html_dir")."/".$resource_name;
			
			if ($file) {
			
				$resource_name =$file;
			
			} else {
			
				report_error("Smarty Template path is-not routed.",array(
					"path" =>$resource_name,
				));
			}
		}
		
		if ($resource_type == 'module') {
			
			$file_find ="modules/html_element/".$resource_name;
			$file =find_include_path($file_find);
			
			if ($file) {
			
				$resource_name =$file;
			
			} else {
			
				report_error("Smarty Template path is-not routed.",array(
					"path" =>$resource_name,
					"file" =>$file_find,
				));
			}
		}
		
		if (is_file($resource_name) && is_readable($resource_name)) {
			
			$template_source =file_get_contents($resource_name);
			$template_timestamp =time();
			
			return true;
		}
		
		report_error('Smarty Template file is-not found.',array(
			"type" =>$resource_type,
			"file" =>$resource_name,
		));
		
		return false;
	}
	
	//-------------------------------------
	// overwrite Smarty::fetch
	public function fetch (
			$resource_name, 
			$cache_id = null, 
			$compile_id = null, 
			$display = false,
			$tmp_vars = array()) {
		
		$reserve =array();
		
		if ($tmp_vars) {
			
			foreach ($tmp_vars as $k => $v) {
			
				$reserve[$k] =$this->_tpl_vars[$k];
				$this->_tpl_vars[$k] =$v;
			}
		}
		
		$source =parent::fetch($resource_name,$cache_id,$compile_id,$display);
		
		if ($tmp_vars) {
			
			foreach ($tmp_vars as $k => $v) {
			
				$this->_tpl_vars[$k] =$reserve[$k];
			}
		}
		
		return $source;
	}
	
	//-------------------------------------
	// overwrite Smarty::_smarty_include
    public function _smarty_include ($params) {
		
		// $params["smarty_include_tpl_file"]
		return parent::_smarty_include($params);
	}
	
	//-------------------------------------
	// overwrite Smarty::_get_plugin_filepath
	public function _get_plugin_filepath ($type, $name) {
		
		$plugin_filename ='modules/smarty_plugin/'.$type.'.'.$name.'.php';
		$found_file =find_include_path($plugin_filename);
		
		if ($found_file) {
			
			return $found_file;	
		}
		
		return parent::_get_plugin_filepath($type,$name);
	}
    
	//-------------------------------------
	// overwrite Smarty::_trigger_fatal_error
	public function _trigger_fatal_error (
			$error_msg, 
			$tpl_file = null, 
			$tpl_line = null,
			$file = null, 
			$line = null, 
			$error_type = E_USER_WARNING) {
		
		$errfile =$tpl_file!==null
				? $tpl_file
				: $file;
		$errline =$tpl_line!==null
				? $tpl_line
				: $line;
		$error_msg ='Smarty fatal error: '.$error_msg;
		
		report_error('Smarty error: '.$error_msg,array(),array(
				"errno" =>$error_type,
				"errfile" =>$errfile,
				"errline" =>$errline));
	}
	
	//-------------------------------------
	// overwrite Smarty::trigger_error
	public function trigger_error (
			$error_msg,
			$error_type = E_USER_WARNING) {
		
		report_warning('Smarty error: '.$error_msg,array(),array(
				"errno" =>$error_type));
	}

	//-------------------------------------
	// LINK系のタグの構築（a/form/buttonタグで使用）
	public function linkage_block ($type, $params, $content, &$template, &$repeat) {
		
		// 開始タグ処理
		if ($repeat) {
		
		// 終了タグ処理
		} else {
			
			$attr_html ="";
			$url_params ="";
			$hidden_input_html ="";
			
			$dest_url =$params["href"]
					? $params["href"]
					: $params["action"];
			$anchor =$params["anchor"];
			
			unset($params["href"]);
			unset($params["action"]);
			unset($params["anchor"]);
			
			// _page
			if ($params["_page"]) {
				
				$dest_url =page_to_url($params["_page"]);
				
				if ( ! $dest_url) {
					
					report_warning("Link page is-not routed.",array(
						"page" =>$params["_page"],
					));
				}
				
				unset($params["_page"]);
			}
			
			// _path
			if ($params["_path"]) {
				
				// 相対指定
				if (preg_match('!^\.!',$params["_path"])) {
					
					$cur =dirname(registry('Request.request_path'));
					$file =registry('Request.html_dir')."/".$cur."/".$params["_path"];
					$dest_url =file_to_url(realpath($file));
				
				} else {
				
					$dest_url =path_to_url($params["_path"]);
				}
				
				if ( ! $dest_url) {
					
					report_warning("Lin path is-not routed.",array(
						"path" =>$params["_path"],
					));
				}
				
				unset($params["_path"]);
			}
			
			// _query
			if ($params["_query"]) {
				
				foreach (explode("&",$params["_query"]) as $kvset) {
					
					list($k,$v) =explode("=",$kvset,2);
					$params["_".$k] =$v;
				}
				
				unset($params["_query"]);
			}
					
			// パラメータの選別
			foreach ($params as $key => $value) {
				
				if (preg_match('!^_(.*)$!',$key,$match)) {
					
					$param_name =$match[1];
					
					if ($type == 'a') {
						
						$url_params .=$param_name.'='.$value.'&';
					
					} elseif ($type == 'form' && $type == "button") {
					
						$hidden_input_html .='<input type="hidden" name="'.
								$param_name.'" value="'.$value.'"/>';
					}
					
				} else {
					
					$attr_html .=' '.$key.'="'.$value.'"';
				}
			}
			
			// URL末尾にパラメータの指定
			if ($url_params) {
			
				$dest_url .=(strpos($dest_url,"?")===false ? "?" : "&").$url_params;
			}
			
			// URL末尾にアンカーの指定
			if ($anchor) {
			
				$dest_url .='#'.$anchor;
			}
			
			$html ="";
			
			// タグ別の処理
			if ($type == 'form') {
			
				$html .='<form method="post" action="'.$dest_url.'"'.$attr_html.'>';
				$html .=$hidden_input_html;
				$html .=$content.'</form>';
				
			} elseif ($type == 'button') {
			
				$html .='<form method="post" action="'.$dest_url.'"'.$attr_html.'>';
				$html .='<input type="submit" value="'.$content.'" />';
				$html .=$content.'</form>';
				
			} elseif ($type == 'a') {
			
				$html .='<a href="'.$dest_url.'"'.$attr_html.'>';
				$html .=$content.'</a>';
			}
			
			print $html;
		}
	}

	//-------------------------------------
	// wrapperタグの処理
	public function smarty_block_wrapper ($params, $content, $smarty, &$repeat) {
		
		// 開始タグ処理
		if ($repeat) {
			
		// 終了タグ処理
		} else {
			
			$wrapper_name =isset($params["name"])
					? $params["name"]
					: $this->get_wrapper_name();
			
			$wrapped_content =$content;
			$wrapper_file_find ="wrapper/".$wrapper_name.".wrapper.html";
			$wrapper_file_found =find_include_path($wrapper_file_find);
			
			if ($wrapper_name && $wrapper_file_found) {
				
				$this->_tpl_vars["_WRAPPER"] =$params;
				$this->_tpl_vars["_WRAPPER"]["_CONTENT"] =$content;
				$wrapped_content =$this->fetch("file:".$wrapper_file);
				unset($this->_tpl_vars["_WRAPPER"]);
			
			} else {
				
				report_warning('Wrapper file "'.$wrapper_file_find.
						'" is-not found.');
			}
			
			return $wrapped_content;
		}
	}
	
	//-------------------------------------
	// ラッパーテンプレート名の取得
	protected function get_wrapper_name () {
	
		return "default";
	}
}