<?php
namespace R\Lib\Console;

class Command
{
    protected $controller_name;
    protected $action_name;
    protected $console;
    /**
     * 初期化
     */
    public function __construct ($controller_name, $action_name)
    {
        $this->controller_name = $controller_name;
        $this->action_name = $action_name;
        $this->console = app()->console;
        $this->init();
    }
    /**
     * 初期化時に呼び出す
     */
    protected function init ()
    {
        //
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
}
