<?php
namespace R\Lib\Controller;
use R\Lib\Form\FormRepositry;
use R\Lib\Http\InputValues;

class HttpController implements FormRepositry
{
    protected $controller_name;
    protected $action_name;
    protected $webroot;
    protected $forms;
    protected $vars = array();
    protected $request = null;
    protected $input = array();
    /**
     * FormRepositry経由で読み込まれるフォームの定義
     */
    protected static $defs = null;
    /**
     * 認証設定
     */
    protected static $access_as = null;
    protected static $priv_required = false;
    /**
     * 初期化
     */
    public function __construct ($controller_name, $action_name)
    {
        $this->controller_name = $controller_name;
        $this->action_name = $action_name;
        $this->webroot = app()->http->getWebrootByPageId($controller_name.".".$action_name);
        // Formを収集して展開
        $this->forms = app()->form->addRepositry($this);
    }
    /**
     * @getter
     * 設定された変数の取得
     */
    public function getVars ()
    {
        return $this->vars;
    }
    public function redirect ($uri, $query_params=array(), $fragment=null)
    {
        return app()->http->response("redirect", $this->uri($uri));
    }
    public function uri ($uri, $query_params=array(), $fragment=null)
    {
        // 相対page_idの解決
        if (is_string($uri) && preg_match('!^id://\.([^\?]+)?(\?.*)?$!', $uri, $match)) {
            $page_id = $match[1];
            $embed_params = $match[2] ? parse_str($match[2]) : array();
            $uri = "id://".$this->controller_name.".".($match[1] ?: $this->action_name).$match[2];
        }
        return $this->webroot->uri($uri, $query_params, $fragment);
    }
    /**
     * act_*の実行
     */
    public function execAct ($args=array())
    {
        $action_method_name = "act_".$this->action_name;
        if ( ! method_exists($this, $action_method_name)) {
            report_warning("Page設定に対応するActionがありません",array(
                "action_method" => get_class($this)."::".$action_method_name,
            ));
            return null;
        }
        return call_user_func_array(array($this,$action_method_name), $args);
    }
    /**
     * inc_*の実行
     */
    public function execInc ($args=array())
    {
        $action_method_name = "inc_".$this->action_name;
        if ( ! method_exists($this, $action_method_name)) {
            return null;
        }
        return call_user_func_array(array($this,$action_method_name), $args);
    }
    /**
     * @implements R\Lib\Form\FormRepositry
     */
    public static function getFormDef ($class_name, $form_name=false)
    {
        if ( ! isset(self::$defs[$class_name])) {
            self::$defs[$class_name] = array();
            // 対象Class内の"static $form_xxx"に該当する変数を収集する
            $ref_class = new \ReflectionClass($class_name);
            foreach ($ref_class->getProperties() as $ref_property) {
                $var_name = $ref_property->getName();
                $is_static = $ref_property->isStatic();
                if ($is_static && preg_match('!^form_(.*)$!',$var_name,$match)) {
                    $found_form_name = $match[1];
                    $def = $class_name::$$var_name;
                    // form_nameの補完
                    $def["form_name"] = $found_form_name;
                    // tmp_storage_nameの補完
                    $dec_class = $ref_property->getDeclaringClass()->getName();
                    if (preg_match('!([a-zA-Z0-9]+)Controller$!',$dec_class,$match)) {
                        $dec_class = str_underscore($match[1]);
                    }
                    $def["tmp_storage_name"] = $dec_class."_".$found_form_name;
                    self::$defs[$class_name][$found_form_name] = $def;
                }
            }
        }
        // 全件取得
        if ($form_name===false) {
            return self::$defs[$class_name];
        }
        // form_nameを指定して取得
        if (isset(self::$defs[$class_name][$form_name])) {
            return self::$defs[$class_name][$form_name];
        }
        // 他のControllerを探索
        if (preg_match('!^(.*?)\.([^\.]+)$!', $form_name, $match)) {
            list(, $ext_controller_name, $ext_form_name) = $match;
            $ext_class_name = "R\\App\\Controller\\".str_camelize($ext_controller_name)."Controller";
            if (class_exists($ext_class_name)) {
                return self::getFormDef($ext_class_name, $ext_form_name);
            }
        }
        report_error("指定されたFormは定義されていません",array(
            "class_name" => $class_name,
            "form_name" => $form_name,
        ));
    }
    /**
     * @implements R\Lib\Auth\Authenticator
     */
    public static function getAuthenticate ()
    {
        $authenticate = array();
        if (static::$access_as) {
            $authenticate["access_as"] =static::$access_as;
        }
        if (static::$access_as) {
            $authenticate["priv_required"] =static::$priv_required;
        }
        return $authenticate;
    }
    /**
     *
     */
    public function getAccessRoleName ()
    {
        return static::$access_as;
    }
    /**
     *
     */
    public function getPrivRequired ()
    {
        return static::$priv_required;
    }

// -- Http系実装

    /**
     * act_*の実行
     */
    public static function getControllerAction ($page_id)
    {
        list($controller_name, $action_name) = explode('.', $page_id, 2);
        $controller_class = 'R\App\Controller\\'.str_camelize($controller_name).'Controller';
        if ( ! class_exists($controller_class)) {
            return null;
        }
        return new $controller_class($controller_name, $action_name);
    }
    /**
     * act_*の実行
     */
    public function execAct2 ($request)
    {
        // 入力
        $this->request = $request;
        $this->input = $request->getAttribute(InputValues::ATTRIBUTE_INDEX);
        // 処理呼び出し
        $response = $this->execAct();
        if ($response) {
            return $response;
        }
        $file = $request->getUri()->getPageFile();
        if (preg_match('!(\.html|/)$!',$file)) {
            if ( ! is_file($file)) {
                return app()->http->response("notfound");
            }
            $vars = $this->getVars();
            $vars["forms"] = $this->forms;
            $html = app()->view($file, $vars);
            return app()->http->response("html", $html);
        } elseif (preg_match('!(\.json|/)$!',$file)) {
            $data = $this->getVars();
            return app()->http->response("json", $data);
        }
        report_error("処理を行いましたが応答を構築できません",array(
            "file" => $file,
            "uri" => $uri,
        ));
    }
    /**
     * inc_*の実行
     */
    public function execInc2 ($request)
    {
        // 入力
        $this->request = $request;
        $this->input = $request->getAttribute(InputValues::ATTRIBUTE_INDEX);
        $this->execInc();
        return $this->getVars();
    }
}
