<?php
namespace R\Lib\Controller;

use R\Lib\Form\FormRepositry;
use R\Lib\Auth\Authenticator;

class HttpController implements FormRepositry, Authenticator
{
    protected $controller_name;
    protected $action_name;
    protected $vars;
    protected $request;
    protected $response;
    protected $forms;
    /**
     * FormRepositry経由で読み込まれるフォームの定義
     */
    protected static $defs = null;
    /**
     * Authenticator経由で読み込まれる認証設定
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
        $this->vars = array();
        $this->request = app()->request;
        $this->response = app()->response;
        $this->forms = app()->form->addRepositry($this);
        $this->vars["forms"] = $this->forms;
    }
    /**
     * @getter
     * 設定された変数の取得
     */
    public function getVars ()
    {
        return $this->vars;
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
                    // tmp_storage_nameの補完
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
        if ($form_name===false) {
            return static::$defs;
        }
        // form_nameを指定して取得
        if (isset(static::$defs[$form_name])) {
            return static::$defs[$form_name];
        }
        // 他のControllerを探索
        if (preg_match('!^(.*?)\.([^\.]+)$!', $form_name, $match)) {
            list(, $ext_controller_name, $ext_form_name) = $match;
            $ext_class_name = "R\\App\\Controller\\".str_camelize($ext_controller_name)."Controller";
            if (class_exists($ext_class_name)) {
                if ($ext_forms = forms()->getRepositry($ext_class_name)) {
                    return $ext_forms[$ext_form_name];
                }
            }
        }
        report_error("指定されたFormは定義されていません",array(
            "class_name" => get_class($this),
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
}
