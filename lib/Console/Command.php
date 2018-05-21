<?php
namespace R\Lib\Console;
use Illuminate\Console\Command as IlluminateCommand;

abstract class Command extends IlluminateCommand
{
    public function __construct ($controller_name=null, $action_name=null)
    {
        // @deprecated
        if ($controller_name && $action_name) {
            $this->controller_name = $controller_name;
            $this->action_name = $action_name;
            $this->console = app()->console;
            $this->init();
        } else {
            parent::__construct();
        }
    }

    protected $controller_name;
    protected $action_name;
    protected $console;
    protected function init ()
    {
        //
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
}
