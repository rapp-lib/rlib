<?php
namespace R\Lib\Webapp;

use R\Lib\Form\FormRepositry;
use R\Lib\Auth\Authenticator;

/**
 *
 */
class Controller_Base implements FormRepositry, Authenticator
{
    protected $request;
    protected $response;
    protected $forms;
    protected $vars;
    /**
     * FormRepositry経由で読み込まれるフォームの定義
     */
    protected static $defs = null;
    /**
     * Authenticator経由で読み込みあれる認証設定
     */
    protected static $access_as = null;
    protected static $priv_required = false;
    /**
     * 初期化
     */
    public function __construct ($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->vars = $this->response;
        $this->forms = form()->addRepositry($this);
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