<?php
namespace R\Lib\View;

use \SmartyBC;

class View_Smarty
{
    public static function fetch ($template_file, $vars=array(), $options=array())
    {
        if ( ! is_file($template_file) || ! is_readable($template_file)) {
            report_error("Smartyテンプレートファイルが読み込めません",array(
                "template_file" => $template_file,
            ));
        }
        $smarty = new SmartyBC();
        // テンプレート基本設定
        $smarty->left_delimiter = '{{';
        $smarty->right_delimiter = '}}';
        $smarty->use_include_path = false;
        $smarty->php_handling = SmartyBC::PHP_ALLOW;
        $smarty->allow_php_templates = true;
        $smarty->escape_html = true;
        // プラグイン読み込み設定
        $plugin_handler_callback = array('\R\Lib\Extention\SmartyPluginLoader',"pluginHandler");
        $smarty->registerDefaultPluginHandler($plugin_handler_callback);
        // 関数名と衝突するプラグインの事前登録
        $plugin_callback = \R\Lib\Extention\SmartyPluginLoader::getCallback("modifier.date");
        $smarty->registerPlugin("modifier", "date", $plugin_callback);
        // キャッシュ/コンパイル済みデータ保存先設定
        $cache_dir = constant("R_APP_ROOT_DIR").'/tmp/smarty_cache/';
        $smarty->setCacheDir($cache_dir);
        $smarty->setCompileDir($cache_dir);
        if ( ! file_exists($cache_dir)) mkdir($cache_dir,0775,true);
        // Assign/Fetch
        $smarty->assign((array)$vars);
        return $smarty->fetch($template_file);
    }
}
