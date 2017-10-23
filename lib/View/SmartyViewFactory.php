<?php
namespace R\Lib\View;

use R\Lib\View\Smarty\SmartyExtended;

class SmartyViewFactory
{
    public function __invoke ($template_file, $vars)
    {
        return $this->fetch($template_file, $vars);
    }
    public function fetch ($template_file, $vars)
    {
        if ( ! is_file($template_file) || ! is_readable($template_file)) {
            report_error("Smartyテンプレートファイルが読み込めません",array(
                "template_file" => $template_file,
            ));
        }
        $smarty = new SmartyExtended();
        $smarty->assign((array)$vars);
        $output_text = $smarty->fetch($template_file);
        return $output_text;
    }
}
