<?php
namespace R\Lib\Webapp;

use R\Lib\Form\FormRepositry;
use R\Lib\Auth\Authenticator;

/**
 *
 */
class Controller_Base implements FormRepositry, Authenticator
{
    protected $vars = array();
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
     * Pageに対応するActionの処理の実行
     */
    public static function invokeAction ($page)
    {
        list($controller_name, $action_name) = explode('.',$page,2);
        $controller_class_name = "R\\App\\Controller\\".str_camelize($controller_name)."Controller";
        $action_method_name = "act_".$action_name;
        if ( ! method_exists($controller_class_name, $action_method_name)) {
            report_warning("Page設定に対応するActionがありません",array(
                "action_method_call" => $controller_class_name."::".$action_method_name,
                "page" => $page,
            ));
            return null;
        }
        // 認証処理
        $auth = $controller_class_name::getAuthenticate();
        if ($auth && ! auth()->authenticate($auth["access_as"], $auth["priv_required"])) {
            report_warning("認証エラー",array(
                "controller_class" => $controller_class_name,
                "auth" => $auth,
                "page" => $page,
            ));
            return null;
        }
        // Actionメソッドの呼び出し
        $controller = new $controller_class_name();
        call_user_func(array($controller,$action_method_name));
        return $controller;
    }
    /**
     * Pageに対応するIncludeActionの処理の実行
     */
    public static function invokeIncludeAction ($page)
    {
        list($controller_name, $action_name) = explode('.',$page,2);
        $controller_class_name = "R\\App\\Controller\\".str_camelize($controller_name)."Controller";
        $action_method_name = "inc_".$action_name;
        if ( ! method_exists($controller_class_name, $action_method_name)) {
            report_warning("Page設定に対応するIncludeActionがありません",array(
                "action_method_call" => $controller_class_name."::".$action_method_name,
                "page" => $page,
            ));
            return null;
        }
        $controller = new $controller_class_name();
        call_user_func(array($controller,$action_method_name));
        return $controller;
    }
    /**
     * 初期化
     */
    public function __construct ()
    {
        $this->vars = array();
        $this->request = app()->request();
        $this->response = app()->response();
        $this->forms = form()->addRepositry($this);
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