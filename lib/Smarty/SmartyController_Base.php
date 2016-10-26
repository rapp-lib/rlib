<?php
namespace R\Lib\Smarty;

//-------------------------------------
//
class SmartyController_Base extends SmartyExtended implements \R\Lib\Form\FormRepositry {

    protected $controller_name;
    protected $action_name;
    protected $vars;
    protected $contexts;
    protected $parent_controller;

    //-------------------------------------
    //
    public function __construct (
            $controller_name="",
            $action_name="",
            $options=array()) {

        parent::__construct();

        $this->init($controller_name,$action_name,$options);
        $this->initController();
    }

    //-------------------------------------
    //
    public function init (
            $controller_name="",
            $action_name="",
            $options=array()) {

        $this->controller_name =$controller_name;
        $this->action_name =$action_name;

        //$this->vars =& $this->_tpl_vars;
        $this->vars =array();
        $this->contexts =array();

        // Smarty上でincにより呼び出された場合、呼び出し元が設定される
        $this->parent =$options["parent_smarty_template"];

        if ($this->parent_controller =$options["parent_controller"]) {

            $this->parent_controller->inherit_state($this, $options);
        }

        // 外部からVarsを追加指定
        foreach ((array)$options["vars"] as $k => $v) {

            $this->vars[$k] =$v;
        }
    }

    //-------------------------------------
    // ほかのControllerに状態を継承する処理
    public function inherit_state ($sub_controller, $options) {

        // 親からVarsを追加指定
        foreach ((array)$this->vars as $k => $v) {

            $sub_controller->vars[$k] =$v;
        }
    }

    //-------------------------------------
    // report出力時の値セット
    public function __report () {

        return array(
            "vars" =>$this->vars,
            "forms" =>$this->forms,
        );
    }

    //-------------------------------------
    // context
    /*
        var_name ... 変数名
        sname ... セッションID（null:ページ固有 / false:無効 / n:縮退）
        fid_enable ... フォーム機能付加
        class_name ... Contextクラス名
    */
    public function context (
            $var_name,
            $sname=null,
            $fid_enable=false,
            $options=array()) {

        $class_name =is_string($options) ? $options :
                ($options["class"] ? $options["class"] : "Context_App");
        $sname_scope =isset($options["scope"])
                ? $options["scope"] : str_underscore($this->controller_name);

        $context =new $class_name;

        $this->$var_name =$context;
        $this->contexts[$var_name] =$context;
        $this->vars[$var_name] =$context;

        if (is_object($sname) && is_subclass_of($sname,"Context_Base")) {

            $sname =$sname->get_sname();

        } else if (is_object($sname)) {

            $sname =str_underscore(get_class($sname));

        } else if (is_string($sname)) {

            $sname =$sname_scope.".".$sname;

        } else if ($sname === 0) {

            $sname =$sname_scope.".".str_underscore($this->action_name);

            if ($fid_enable===true) {

                $fid_enable =str_underscore($this->controller_name)
                        .".".str_underscore($this->action_name);
            }

        } else if (is_numeric($sname) && $sname > 0) {

            $action_name =$this->action_name;
            $action_name =str_underscore($action_name);
            $action_name =explode("_",$action_name);
            $action_name =array_slice($action_name,0,-$sname);
            $action_name =implode("_",$action_name);

            $sname =$sname_scope
                    ."-".$action_name;

            if ($fid_enable===true) {

                $fid_enable =str_underscore($this->controller_name)
                        .".".$action_name."*";
            }
        }

        // fid_enable設定によるURL書き換え処理の登録
        if ($fid_enable && $sname) {

            $fid_name ="f_".substr(md5($sname),0,5);

            // fid_check指定がある場合、正常なfidがURLで渡らなければエラーとなる
            if ( ! strlen($_REQUEST[$fid_name]) && $options["fid_check"]) {

                return false;
            }

            $fid =strlen($_REQUEST[$fid_name])
                    ? $_REQUEST[$fid_name]
                    : substr(md5(mt_rand(1,999999)),0,5);

            $sname .="-".$fid;

            // [Deprecated]全てのURLの書き換えを行う処理方式
            if (registry("Context.fid_pass_by_rewrite")) {

                output_rewrite_var($fid_name,$fid);
                output_rewrite_var("_noindex","1");

            } else {

                add_url_rewrite_rule($fid_enable, $fid_name, $fid, array(
                    "sname" =>$sname,
                ));
            }
        }

        $context->bind_session($sname);

        return $context;
    }

    //-------------------------------------
    //
    public function before_act () {
    }

    //-------------------------------------
    //
    public function after_act () {
    }

// -- SmartyExtendedから移行した機能

    public $_tpl_vars_DEPRECATED;

    /**
     * @deprecated
     * @override
     *
     * $this->cの取得時に警告を表示しないために実装
     */
    public function __get ($name)
    {
        return $this->{$name};
    }

    /**
     * @deprecated
     * @override
     *
     * $this->cの設定時に警告を表示しないために実装
     */
    public function __set ($name, $value) {

        $this->{$name} =$value;
    }

    //-------------------------------------
    // widgetリソース解決
    public function resolve_resource_widget_DEPRECATED ($resource_name, $load=false) {

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
            $this->vars["widget"] =$widget_class->vars;
        }

        return $file;
    }

    //-------------------------------------
    // pathリソース解決
    public function resolve_resource_path_DEPRECATED ($resource_name, $load=false) {

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
    public function resolve_resource_module_DEPRECATED ($resource_name, $load=false) {

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
    public function fetch_src_DEPRECATED (
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
    // overwrite Smarty::_trigger_fatal_error
    public function _trigger_fatal_error_DEPRECATED (
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
    public function trigger_error_DEPRECATED (
            $error_msg,
            $error_type = E_USER_WARNING) {

        report_warning('Smarty error: '.$error_msg,array(),array(
                "errno" =>$error_type));
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
        array_extract($this->vars);
        $resource->assign($this->vars);

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

        // -- Assign
        $resource->assign((array)$this->response);
        $resource->assign("request", $this->request);
        $resource->assign("forms", $this->forms);

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

// -- 新Controllerに継承予定の機能

    protected $request;
    protected $response;
    protected $forms;
    protected static $defs = null;

    public function initController ()
    {
        $this->request = request();
        $this->response = $this->request->response();
        $this->forms = form()->addRepositry($this);
    }
    /**
     * @implements R\Lib\Form\FormRepositry
     */
    public static function getFormDef ($class_name, $form_name=null)
    {
        if ( ! isset(static::$defs)) {
            static::$defs = array();
            // 対象Class内の"static $form_xxx"に該当する変数を収集する
            $ref_class = new \ReflectionClass($class_name);
            foreach ($ref_class->getProperties() as $ref_property) {
                $var_name = $ref_property->getName();
                $is_static = $ref_property->isStatic();
                if ($is_static && preg_match('!^form_(.*)$!',$var_name,$match)) {
                    $found_form_name = $match[1];
                    $def = static::$$var_name;
                    // form_nameの補完
                    $def["form_name"] = $found_form_name;
                    // form_full_nameの補完
                    $dec_class = $ref_property->getDeclaringClass()->getName();
                    if (preg_match('!([a-zA-Z0-9]+)Controller$!',$dec_class,$match)) {
                        $dec_class = str_underscore($match[1]);
                    }
                    $def["tmp_storage_name"] = $dec_class.".".$found_form_name;
                    static::$defs[$found_form_name] = $def;
                }
            }
        }
        // 全件取得
        if ( ! isset($form_name)) {
            return static::$defs;
        }
        // form_nameを指定して取得
        if (isset(static::$defs[$form_name])) {
            return static::$defs[$form_name];
        }
        report_error("指定されたFormは定義されていません",array(
            "class_name" => get_class($this),
            "form_name" => $form_name,
        ));
    }
}
