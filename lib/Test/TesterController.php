<?php
namespace R\Lib\Test;
use R\Lib\Http\HttpController;

use PHPUnit_TextUI_TestRunner;
use PHPUnit_Framework_Exception;

class TesterController extends HttpController
{
    public function act_sandbox()
    {
        // PHPファイルの読み込み
        $root_dir = constant("R_APP_ROOT_DIR")."/devel/sandbox";
        $tests = array();
        foreach (glob($root_dir."/*.php") as $php_file) {
            if (preg_match('!([^/]+)\.php$!', $php_file, $_)) {
                require_once $php_file;
                $tests[$_[1]] = array();
            }
        }
        // 呼び出しメソッドの収集
        foreach ($tests as $class=>$methods) {
            $tests[$class] = get_class_methods($class);
        }
        $this->vars["tests"] = $tests;
        // 実行
        if (preg_match('!^([^:]+?)(?:::(.*?))?$!', $this->input["test"], $_)) {
            list(, $class, $method) = $_;
            $methods = $tests[$class];
            if ($method) $methods = in_array($method, $methods) ? array($method) : array();
            $object = new $class();
            foreach ($methods as $method) {
                ob_start();
                $ret = call_user_func(array($object, $method));
                $results[$class."::".$method] = array(
                    "output"=>ob_get_clean(),
                    "return"=>$ret,
                );
            }
            $this->vars["results"] = $results;
        }
        $this->template = "sandbox-index.html";
    }
    private $template = "index.html";
    protected function getTemplateFile()
    {
        return constant("R_LIB_ROOT_DIR")."/assets/tester/html/".$this->template;
    }
    protected function getTemplateVars()
    {
        $vars = parent::getTemplateVars();
        $vars["header_file"] = constant("R_LIB_ROOT_DIR")."/assets/tester/html/header.html";
        $vars["footer_file"] = constant("R_LIB_ROOT_DIR")."/assets/tester/html/footer.html";
        return $vars;
    }
}
