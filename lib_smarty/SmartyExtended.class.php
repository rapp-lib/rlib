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
					
					} elseif ($type == 'form' || $type == "button") {
					
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
	// select系のタグの構築（select/radioselect/checklistタグで使用）
	public function input_type_select_family (
			$params, 
			$preset_value, 
			$postset_value, 
			& $template) {
		
		$selected_value =isset($postset_value)
				? $postset_value
				: $preset_value;
		
		$op_keys =array(
			"type",
			"name",
			"assign", // 指定した名前で部品のアサイン
			"options", // List名の指定
			"options_param", // ListOptions::optionsの引数
			"parent_id", // 連動対象の要素のID
			"parents_param", // ListOptions::parentsの引数
			"zerooption", // 非選択要素を先頭に追加
		);
		$attr_html ="";
		
		// id属性の補完
		$params["id"] =$params["id"]
				? $params["id"]
				: sprintf("ELM%09d",mt_rand());
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		$list_options =obj("ListOptions")->get_instance($params["options"]);
		$options =$list_options->options($params["options_param"]);
		
		if (isset($params["zerooption"])) {
		
			$options =array("" =>$params["zerooption"]) + $options;
		}
		
		$html =array(
			"full" =>"",
			"head" =>"",
			"foot" =>"",
			"options" =>array(),
		);
		
		if ($params["type"] == "select") {
					
			$html["head"] ='<select name="'.$params["name"].'"'.$attr_html.'>'."\n";
			$html["foot"] ='</select>';
			
			foreach ($options as $option_value => $option_label) {
				
				$selected =(string)$option_value == (string)$selected_value;
				$html["options"][$option_value] ='<option'
						.' value="'.$option_value.'"'
						.($selected ? ' selected="selected"' : '')
						.'>'.$option_label.'</option>'."\n";
			}
			
			// 親要素との連動
			if ($params["parent_id"]) {
				
				$parents =$list_options->parents($params["parents_param"]);
				
				$html["foot"] .='<script>'
						.'select_sync_parent("'.$params['id'].'",'
						.'"'.$params['parent_id'].'",'
						.array_to_json($parents).');</script>';
			}
			
		} elseif ($params["type"] == "radioselect") {
		
			$html["head"] ='';
			$html["foot"] ='';
			
			foreach ($options as $option_value => $option_label) {
				
				$checked =(string)$option_value == (string)$selected_value;
				$html["options"][$option_value] =
						'<nobr><label>'.'<input type="radio"'
						.' name="'.$params["name"].'"'
						.' value="'.$option_value.'"'.$attr_html
						.($checked ? ' checked="checked"' : '')
						.'>'.$option_label.'</label></nobr>'."\n";
			}
			
		} elseif ($params["type"] == "checklist") {
		
			$selected_value =is_array($selected_value)
					? $selected_value
					: array();
		
			$html["head"] ='';
			$html["foot"] ='';
			
			foreach ($options as $option_value => $option_label) {
				
				$checked =in_array($option_value,$selected_value);
				$html["options"][$option_value] =
						'<nobr><label>'.'<input type="checkbox"'
						.' name="'.$params["name"].'['.$option_value.']'.'"'
						.' value="'.$option_value.'"'.$attr_html
						.($checked ? ' checked="checked"' : '')
						.'>'.$option_label.'</label></nobr>'."\n";
			}
		}
		
		
		$html["full"] =$html["head"].implode("",$html["options"]).$html["foot"];
		
		// テンプレート変数へのアサイン
		if ($params["assign"]) {
			
			$ref =& ref_array($template->_tpl_vars,$params["assign"]);
			$ref =$html;
		}
		
		return $html["full"];
	}
}