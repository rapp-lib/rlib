<?php
namespace R\Lib\Http;
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

    public function __construct ($controller_name, $action_name)
    {
        $this->controller_name = $controller_name;
        $this->action_name = $action_name;
        $this->webroot = app()->http->getWebrootByPageId($controller_name.".".$action_name);
        // Formを収集して展開
        $this->forms = app()->form->addRepositry($this);
    }
    public function getVars ()
    {
        return $this->vars;
    }
    public function resolveRelativePageId ($page_id)
    {
        // 相対page_idの解決
        if (preg_match('!^\.([^\?\.]+)?$!', $page_id, $match)) {
            $page_id = $this->controller_name.".".($match[1] ?: $this->action_name);
        }
        return $page_id;
    }

// -- act実装向け機能

    public function redirect ($uri, $query_params=array(), $fragment=null)
    {
        return app()->http->response("redirect", $this->uri($uri, $query_params, $fragment));
    }
    public function response ($type, $data=null)
    {
        return app()->http->response($type, $data);
    }
    public function uri ($uri, $query_params=array(), $fragment=null)
    {
        // 相対page_idの解決
        if (is_string($uri) && preg_match('!^id://([^\?]+)$!', $uri, $match)) {
            $page_id = $match[1];
            $uri = array("page_id"=>$page_id);
        }
        if (is_array($uri) && isset($uri["page_id"])) {
            $uri["page_id"] = $this->resolveRelativePageId($uri["page_id"]);
        }
        return $this->webroot->uri($uri, $query_params, $fragment);
    }

// -- Form系実装

    /**
     * FormRepositry経由で読み込まれるフォームの定義
     */
    protected static $defs = null;
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
     * lib/Http/PageAction.php
     */
    public function execAct2 ($request)
    {
        // 入力
        $this->request = $request;
        $this->input = $request->getAttribute(InputValues::ATTRIBUTE_INDEX);
        // 処理呼び出し
        $response = $this->execAct();
        if ($response) return $response;
        // Responseを返さない場合、Viewで処理
        $file = $request->getUri()->getPageFile();
        if ( ! is_file($file)) return app()->http->response("notfound");
        $vars = $this->getVars();
        $vars["forms"] = $this->forms;
        $vars["input"] = $this->input;
        $vars["request"] = $this->request;
        $vars["enum"] = app()->enum;
        $html = app()->view()->fetch($file, $vars);
        return app()->http->response("html", $html);
    }
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
     * lib/Http/PageAction
     */
    public function execInc2 ($request)
    {
        // 入力
        $this->request = $request;
        $this->input = $request->getAttribute(InputValues::ATTRIBUTE_INDEX);
        $this->execInc();
        return $this->getVars();
    }
    public function execInc ($args=array())
    {
        $action_method_name = "inc_".$this->action_name;
        if ( ! method_exists($this, $action_method_name)) {
            return null;
        }
        return call_user_func_array(array($this,$action_method_name), $args);
    }
}
