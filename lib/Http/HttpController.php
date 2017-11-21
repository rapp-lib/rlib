<?php
namespace R\Lib\Http;
use R\Lib\Form\FormRepositry;
use R\Lib\Http\InputValues;

class HttpController implements FormRepositry
{
    protected $uri;
    protected $vars = array();
    protected $forms = null;
    protected $request = null;
    protected $input = array();

    public function __construct ($uri)
    {
        $this->uri = $uri;
    }

// -- act実装向け機能

    protected function redirect ($uri, $query_params=array(), $fragment=null)
    {
        return app()->http->response("redirect", $this->uri($uri, $query_params, $fragment));
    }
    protected function response ($type, $data=null)
    {
        return app()->http->response($type, $data);
    }
    protected function uri ($uri, $query_params=array(), $fragment=null)
    {
        return $this->uri->getRelativeUri($uri, $query_params, $fragment)->withToken();
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

    public function run ($request)
    {
        $result = $this->invokeAction($request);
        if ( ! $result["has_action"]) {
            report_warning("URLに対応するActionがありません",array("uri"=>$this->uri));
        }
        // ResponseがあればHTMLを処理せず応答
        if ($result["response"]) return $result["response"];
        $file = $this->uri->getPageFile();
        // HTMLファイルがなければ404応答
        if ( ! is_file($file)) return app()->http->response("notfound");
        // varsの組み立て
        $vars = $this->vars;
        $vars["forms"] = $this->forms;
        $vars["input"] = $this->input;
        $vars["request"] = $this->request;
        $vars["enum"] = app()->enum;
        // HTMLファイルを応答
        $route = $this->uri->getRoute();
        $view = app()->view($route["view"] ?: "default");
        $html = $view->fetch($file, $vars);
        return app()->http->response("html", $html);
    }
    public function invokeAction ($request)
    {
        $result = array();
        // 入力
        $this->request = $request;
        $this->input = $request->getAttribute(InputValues::ATTRIBUTE_INDEX);
        $this->forms = app()->form->addRepositry($this);
        $this->flash = app()->session->getFlash();
        list(,$action_name) = explode('.', $this->uri->getPageId(), 2);
        // 処理呼び出し
        $action_method_name = "act_".$action_name;
        if (method_exists($this, $action_method_name)) {
            $result["has_action"] = true;
            $result["response"] = call_user_func(array($this, $action_method_name));
        }
        $result["vars"] = $this->vars;
        return $result;
    }
}
