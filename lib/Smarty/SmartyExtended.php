<?php
namespace R\Lib\Smarty;
use SmartyBC;
use R\Util\Reflection;

//-------------------------------------
//
class SmartyExtended extends SmartyBC {

    public $_tpl_vars;
    public $current_form;

    //-------------------------------------
    // 初期化
    public function __construct () {

        parent::__construct();

        $cache_dir =registry("Path.tmp_dir").'/smarty_cache/';

        $this->left_delimiter ='{{';
        $this->right_delimiter ='}}';
        $this->addPluginsDir("modules/smarty_plugin/");
        $this->addPluginsDir(__DIR__."/../../plugins/Smarty/smarty_plugin/");
        $this->registerDefaultPluginHandler(array($this,"load_plugin"));
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

    /**
     * Smarty::registerDefaultPluginHandlerに登録するプラグイン読み込み処理
     */
    public function load_plugin ($name, $type, $template, &$callback, &$script, &$cacheable)
    {
        $plugin_class = 'R\\Plugin\\Smarty\\Smarty'.str_camelize($type).'\\'.str_camelize($name);
        $callback_func ="smarty_".$type."_".$name;

        // [DEPRECATED] 関数による定義の探索
        $plugin_file = __DIR__."/../../plugins/Smarty/smarty_plugin/".$type.".".$name.".php";

        if ( ! is_callable($callback_func) && file_exists($plugin_file)) {
            require_once($plugin_file);
            $script = $callback_func;
            $callback = $callback_func;
            return true;
        }

        // Pluginクラスの読み込み
        $callback_method = array($plugin_class,$callback_func);

        if (class_exists($plugin_class)) {
            if (is_callable($callback_method)) {
                //$defined_at = Reflection::getDefinedAt($callback_method);
                //$script = $defined_at["file"];
                $callback = $callback_method;
                return true;
            }
        }

        return false;
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
    public function resolve_resource_path_transit ($resource_name, $load=false) {

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
    public function resolve_resource_module_transit ($resource_name, $load=false) {

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

        $parent =$parent ? $parent : $this->parent;

        $resource =$this->createTemplate($template, $cache_id, $compile_id, $parent, false);

        // 変数アサイン
        array_extract($this->_tpl_vars);
        $resource->assign($this->_tpl_vars);

        // 追加の変数アサイン
        array_extract($tpl_vars);
        $resource->assign($tpl_vars);

        $resource->assign(array(
            "_REQUEST" =>$_REQUEST,
            "_SERVER" =>$_SERVER,
        ));

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

        // SmartyがExceptionを補足するとReport出力を消してしまうことに対する対処
        report_buffer_start();

        $html_source =$resource->fetch(
                $resource,
                $cache_id,
                $compile_id,
                $parent,
                $display,
                $merge_tpl_vars,
                $no_output_filter);

        report_buffer_end();

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

            if ($type == "form") {
                $this->current_form = $params;
            }

        // 終了タグ処理
        } else {

            if ($type == "form") {
                unset($this->current_form);
            }

            $attr_html ="";
            $url_params =array();
            $hidden_input_html ="";

            $dest_url =$params["href"]
                    ? $params["href"]
                    : $params["action"];
            $anchor =$params["anchor"];
            $method =$params["method"]
                    ? $params["method"]
                    : "post";
            $values =$params["values"];

            unset($params["href"]);
            unset($params["action"]);
            unset($params["anchor"]);
            unset($params["method"]);
            unset($params["values"]);

            // URLの決定

            // href/actionによるURL指定
            if ($dest_url) {

                $dest_url =apply_url_rewrite_rules($dest_url);

            // _pageによるURL指定
            } else if ($params["_page"]) {

                $dest_url =page_to_url($params["_page"]);

                if ( ! $dest_url) {

                    report_warning("Link page is-not routed.",array(
                        "page" =>$params["_page"],
                    ));
                }

                unset($params["_page"]);

            // _pathでのURL指定
            } elseif ($params["_path"]) {

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

            // URLパラメータの付与

            // _query
            if ($params["_query"]) {

                if (is_string($params["_query"])) {

                    foreach (explode("&",$params["_query"]) as $kvset) {

                        list($k,$v) =explode("=",$kvset,2);
                        $url_params[$k] =$v;
                    }

                } else {

                    foreach ($params["_query"] as $k => $v) {

                        $url_params[$k] =$v;
                    }
                }

                unset($params["_query"]);
            }

            // パラメータの選別
            foreach ($params as $key => $value) {

                if (preg_match('!^_(.*)$!',$key,$match)) {

                    $param_name =$match[1];

                    if (is_array($url_params[$param_name]) && is_array($value)) {

                        $url_params[$param_name] =array_merge($url_params[$param_name],$value);

                    } else {

                        $url_params[$param_name] =$value;
                    }

                } else {

                    $attr_html .=' '.$key.'="'.$value.'"';
                }
            }

            $dest_url =url($dest_url,$url_params,$anchor);

            $html ="";

            // タグ別の処理
            if ($type == 'form') {

                $html .='<form method="'.$method.'" action="'.$dest_url.'"'.$attr_html.'>';
                $html .=$hidden_input_html;
                $html .=$content.'</form>';

            } elseif ($type == 'button') {

                $html .='<form method="'.$method.'" action="'.$dest_url.'"'.$attr_html.'>';
                $html .='<input type="submit" value="'.$content.'" /></form>';

            } elseif ($type == 'a') {

                $html .='<a href="'.$dest_url.'"'.$attr_html.'>';
                $html .=$content.'</a>';
            }

            print $html;
        }
    }
}
