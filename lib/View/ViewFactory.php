<?php
namespace R\Lib\View;

class ViewFactory
{
    public function __invoke ($template_file, $vars=array(), $options=array())
    {
        return $this->fetch($template_file, $vars, $options);
    }
    public function fetch ($template_file, $vars=array(), $options=array())
    {
        return View_Smarty::fetch($template_file, $vars, $options);
    }
}
