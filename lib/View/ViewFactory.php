<?php
namespace R\Lib\View;

class ViewFactory
{
    protected $instances = array();
    public function __invoke ($template_file, $vars=array(), $options=array())
    {
        return $this->getView()->fetch($template_file, $vars, $options);
    }
    public function getView ($name="default")
    {
        if ( ! $this->instances[$name]) {
            $class = app()->config("view.driver.".$name.".class");
            if ( ! $class) $class = 'R\App\View\View_App';
            $this->instances[$name] = new $class();
        }
        return $this->instances[$name];
    }
}
