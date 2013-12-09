<?php
	
	require_once(RLIB_ROOT_DIR."/core/smarty3/Smarty.class.php");
	
//-------------------------------------
// 
class SmartyBC extends Smarty {} 
class SmartyExtended extends SmartyBC {
	
	public $_tpl_vars;
	
	//-------------------------------------
	// 初期化
	public function __construct () {
			
		parent::__construct();
		
		$cache_dir =registry("Path.tmp_dir").'/smarty_cache/';
		
		$this->left_delimiter ='{{';
		$this->right_delimiter ='}}';
		$this->addPluginsDir("modules/smarty_plugin/");
		$this->setCacheDir($cache_dir);
		$this->setCompileDir($cache_dir);
		
		$this->use_include_path =true;
		
		if ( ! file_exists($cache_dir) 
				&& is_writable(dirname($cache_dir))) {
			
			mkdir($cache_dir,0777);
		}
		
		$this->php_handling =self::PHP_ALLOW;
		$this->allow_php_templates =true;
	}

	//-------------------------------------
	// 無効なメソッド呼び出し
	public function __call ($method, $args) {
	
		report_warning(get_class($this).'::'.$method.' is-not callable. ');
	}
	
	//-------------------------------------
	// メンバ変数取得(overload Smarty::__get)
	public function __get ($name) {
	
		return $this->{$name};
    }

	//-------------------------------------
	// メンバ変数設定(overload Smarty::__set)
	public function __set ($name, $value) {
	
		$this->{$name} =$value;
	}
	
	//-------------------------------------
	// widgetリソース解決
	public function resolve_resource_widget_DEDICATED ($resource_name, $load=false) {
		
		if (preg_match('!^/!',$resource_name)) {
			
			$path =$resource_name;
			$page =path_to_page($path);
		
		} else {
			
			$page =$resource_name;
			$path =page_to_path($path);
		}
		
		// テンプレートファイルの対応がない場合のエラー
		if ( ! $page || ! $path) {
		
			report_error("Smarty Template page is-not routed.",array(
				"widget" =>$resource_name,
			));
		}
		
		// テンプレートファイル名の解決
		$file =page_to_file($page);
		
		// Widget名の解決
		list($widget_name, $action_name) =explode('.',$page,2);
		$widget_class_name =str_camelize($widget_name)."Widget";
		$action_method_name ="act_".$action_name;
		
		// テンプレートファイルが読み込めない場合のエラー
		if ( ! is_file($file) || ! is_readable($file)) {
		
			report_error('Smarty Template file is-not found.',array(
				"widget" =>$resource_name,
				"file" =>$file,
			));
		}
		
		// Widget起動エラー
		if ( ! class_exists($widget_class_name)
				|| is_callable(array($widget_class,$action_method_name))) {
		
			report_error("Widget startup failur.",array(
				"widget" =>$resource_name,
				"widget_class_name" =>$widget_class_name,
				"action_method_name" =>$action_method_name,
			));
		}
		
		// Widget処理の起動
		if ( ! $load) {
			
			$widget_class =obj($widget_class_name);
			$widget_class->init($widget_name,$action_name,$this);
			$widget_class->before_act();
			$widget_class->$action_method_name();
			$widget_class->after_act();
			$this->_tpl_vars["widget"] =$widget_class->_tpl_vars;
		}
		
		return $file;
	}
	
	//-------------------------------------
	// pathリソース解決
	public function resolve_resource_path ($resource_name, $load=false) {
	
		$file =path_to_file($resource_name);
		
		// テンプレートファイルが読み込めない場合のエラー
		if ( ! is_file($file) || ! is_readable($file)) {
		
			report_error('Smarty Template file is-not found.',array(
				"path" =>$resource_name,
				"file" =>$file,
			));
		}
		
		return $file;
	}
	
	//-------------------------------------
	// moduleリソース解決
	public function resolve_resource_module ($resource_name, $load=false) {
	
		$file_find ="modules/html_element/".$resource_name;
		$file =find_include_path($file_find);
		
		// テンプレートファイルが読み込めない場合のエラー
		if ( ! $file || ! is_file($file) || ! is_readable($file)) {
		
			report_error('Smarty Template file is-not found.',array(
				"module" =>$resource_name,
				"file_find" =>$file_find,
				"file" =>$file,
			));
		}
		
		return $file;
	}
	
	//-------------------------------------
	// テンプレート文字列を直接fetch
	public function fetch_src (
			$tpl_source, 
			$tpl_vars=array(),
			$security=false) {
		
		return $this->fetch(
				"eval:".$tpl_source, 
				null, 
				null, 
				null, 
				false, 
				true, 
				false,
				$tpl_vars,
				$security);
	}
	
	//-------------------------------------
	// overwrite Smarty::fetch
	public function fetch (
			$template = null, 
			$cache_id = null, 
			$compile_id = null, 
			$parent = null, 
			$display = false, 
			$merge_tpl_vars = true, 
			$no_output_filter = false,
			$tpl_vars = array(),
			$security = false) {
		
		$resource =$this->createTemplate(
				$template,
				$cache_id, 
				$compile_id);
				
		// 変数アサイン
		array_extract($this->_tpl_vars);
		$resource->assign($this->_tpl_vars);
		
		// 追加の変数アサイン
		array_extract($tpl_vars);
		$resource->assign($tpl_vars);
		
		// テンプレート記述の制限設定
		if ($security) {
		
			$policy =is_string($security) || is_object($security)
					? $security
					: null;
			
			if (is_array($security)) {
				
				$policy =new Smarty_Security($this);
				
				foreach ($security as $k => $v) {
					
					$policy->$k =$v;
				}
			}
			
			$resource->enableSecurity();	
		}
		
		$html_source =$resource->fetch(
				$template, 
				$cache_id, 
				$compile_id, 
				$parent, 
				$display, 
				$merge_tpl_vars, 
				$no_output_filter);
		
		unset($resource);
		
		return $html_source;
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
	public function linkage_block ($type, $params, $content, $template, $repeat) {
		
		// 開始タグ処理
		if ($repeat) {
		
		// 終了タグ処理
		} else {
			
			$attr_html ="";
			$url_params =array();
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
					$url_params[$k] =$v;
				}
				
				unset($params["_query"]);
			}
					
			// パラメータの選別
			foreach ($params as $key => $value) {
				
				if (preg_match('!^_(.*)$!',$key,$match)) {
					
					$param_name =$match[1];
					
					if ($type == 'a') {
						
						$url_params[$param_name] =$value;
					
					} elseif ($type == 'form' || $type == "button") {
					
						$hidden_input_html .='<input type="hidden" name="'.
								$param_name.'" value="'.$value.'"/>';
					}
					
				} else {
					
					$attr_html .=' '.$key.'="'.$value.'"';
				}
			}
			
			$dest_url =url($dest_url,$url_params,$anchor);
			
			$html ="";
			
			// タグ別の処理
			if ($type == 'form') {
			
				$html .='<form method="post" action="'.$dest_url.'"'.$attr_html.'>';
				$html .=$hidden_input_html;
				$html .=$content.'</form>';
				
			} elseif ($type == 'button') {
			
				$html .='<form method="post" action="'.$dest_url.'"'.$attr_html.'>';
				$html .='<input type="submit" value="'.$content.'" /></form>';
				
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
			$template) {
		
		$selected_value =isset($postset_value)
				? $postset_value
				: $preset_value;
		
		$op_keys =array(
			"type",
			"id",
			"name",
			"assign", // 指定した名前で部品のアサイン
			
			"values", // 値リストの配列指定
			"options", // List名の指定
			"options_params", // List::optionsの引数
			"parent_id", // 連動対象の要素のID
			"parents_params", // List::parentsの引数
			
			// Checklist以外
			"zerooption", // 先頭の非選択要素の指定
			
			// Selectのみ
			"nozerooption", // 非選択要素を自動的に追加しない指定
		);
		$attr_html ="";
		
		// id属性の補完
		$params["id"] =$params["id"]
				? $params["id"]
				: sprintf("ELM%09d",mt_rand());
		
		// optionsの配列としてparamsを渡す
		if (is_array($params["options"])) {
			
			$list_name =array_shift($params["options"]);
			$params["options_params"] =$params["options"];
			$params["options"] =$list_name;
		}

		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}

		// valuesで配列によってリストを渡す
		if ( ! $params["options"] && $params["values"]) {

			$list_options =get_list(null,$this);
			$options =$params["values"];

		} else {

			$list_options =get_list($params["options"],$this);
			$options =$list_options->options($params["options_params"]);
		}

		// 空白選択の挿入(Checklist以外)
		if ($params["type"] != "checklist" && isset($params["zerooption"])) {
		
			$options =array("" =>$params["zerooption"]) + $options;
		
		// Select要素には空白要素を自動挿入
		} elseif ($params["type"] == "select" 
				&& ! isset($params["nozerooption"])) {
		
			$options =array("" =>"") + $options;
		}
		
		$html =array(
			"full" =>"",
			"head" =>"",
			"foot" =>"",
			"options" =>array(),
		);
		
		if ($params["type"] == "select") {
					
			$html["head"] ='<select id="'.$params["id"].'"'
					.' name="'.$params["name"].'"'.$attr_html.'>'."\n";
			$html["foot"] ='</select>';
			
			foreach ($options as $option_value => $option_label) {
				
				$selected =(string)$option_value === (string)$selected_value;
				$html["options"][$option_value] ='<option'
						.' value="'.$option_value.'"'
						.($selected ? ' selected="selected"' : '')
						.'>'.$option_label.'</option>'."\n";
			}
			
		} elseif ($params["type"] == "radioselect") {
		
			$html["head"] ='';
			$html["foot"] ='';
			
			foreach ($options as $option_value => $option_label) {
				
				$checked =(string)$option_value === (string)$selected_value;
				$html["options"][$option_value] =
						'<nobr><label>'.'<input type="radio"'
						.' name="'.$params["name"].'"'
						.' value="'.$option_value.'"'.$attr_html
						.($checked ? ' checked="checked"' : '')
						.'> <span class="labeltext">'.$option_label
						.'</span></label></nobr> &nbsp;'."\n";
			}
			
		} elseif ($params["type"] == "checklist") {
		
			if (is_string($selected_value)) {
				
				$selected_value =unserialize($selected_value);
			
			} elseif ( ! is_array($selected_value)) {
				
				$selected_value =(array)$selected_value;
			}
			
			$html["head"] ='';
			$html["foot"] ='';
			
			foreach ($options as $option_value => $option_label) {
				
				$checked =false;
				
				foreach ((array)$selected_value as $a_selected_value) {
				
					if ((string)$option_value === (string)$a_selected_value) {
						
						$checked =true;
						break;
					}
				}
				
				$html["options"][$option_value] =
						'<input type="hidden" name="'.$params['name']
						.'['.$option_value.']" value="" />'."\n"
						.'<nobr><label>'.'<input type="checkbox"'
						.' name="'.$params["name"].'['.$option_value.']'.'"'
						.' value="'.$option_value.'"'.$attr_html
						.($checked ? ' checked="checked"' : '')
						.'> <span class="labeltext">'.$option_label
						.'</span></label></nobr> &nbsp;'."\n";
			}
			
		} elseif ($params["type"] == "multiselect") {
		
			if (is_string($selected_value)) {
				
				$selected_value =unserialize($selected_value);
			
			} elseif ( ! is_array($selected_value)) {
				
				$selected_value =(array)$selected_value;
			}
					
			$html["head"] ='<select id="'.$params["id"].'"'
					.' name="'.$params["name"].'[]" multiple="multiple"'
					.$attr_html.'>'."\n";
			$html["foot"] ='</select>';
			
			foreach ($options as $option_value => $option_label) {
				
				$selected =false;
				
				foreach ((array)$selected_value as $a_selected_value) {
				
					if ((string)$option_value === (string)$a_selected_value) {
						
						$selected =true;
						break;
					}
				}
				
				$html["options"][$option_value] ='<option'
						.' value="'.$option_value.'"'
						.($selected ? ' selected="selected"' : '')
						.'>'.$option_label.'</option>'."\n";
			}
			
		}
			
		// 親要素との連動
		if ($params["parent_id"]) {
			
			// get_list_json.html?parent=xによる動的関連付け
			// ※現状ではtype="select"でのみ使用可能
			if ($params["parent_rel_url"]) {
				
				$pair ='"'.url($params["parent_rel_url"],array()).'"';
				
			// list->parents()による静的な関連付け
			} else {
			
				$parents =$list_options->parents($params["parents_params"]);
				$pair =array_to_json($parents);
				
				if ($params["type"] == "radioselect" || $params["type"] == "checklist") {
					
					foreach ($html["options"] as $k => $v) {
					
						$html["options"][$k] ='<span class="_listitem">'.$v.'</span>';
					}
					
					$html["head"] ='<span id="'.$params["id"].'">'.$html["head"];
					$html["foot"] =$html["foot"].'</span>';
				}
			}
			
			$html["foot"] .='<script>/*<!--*/ rui.require("rui.syncselect",function(){ '
					.'rui.syncselect("'.$params['id'].'",'.'"'.$params['parent_id'].'",'
					.$pair.',"'.$params["type"].'"); }); /*-->*/</script>';
		}
		
		$html["full"] =$html["head"].implode("",$html["options"]).$html["foot"];
		
		// テンプレート変数へのアサイン
		if ($params["assign"]) {
			
			$template->assign($params["assign"],$html);
			
			return null;
		}
		
		return $html["full"];
	}
}