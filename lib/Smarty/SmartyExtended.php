<?php
/**
 * @dep R\Plugin\Smarty\SmartyPlugin\*
 */
namespace R\Lib\Smarty;
use SmartyBC;

/**
 *
 */
class SmartyExtended extends SmartyBC
{
    /**
     * Form領域の状態スタック
     */
    protected $form_stack_top = null;
    protected $form_stack = array();

    /**
     * @override
     */
    public function __construct ()
    {
        parent::__construct();

        // テンプレート基本設定
        $this->left_delimiter ='{{';
        $this->right_delimiter ='}}';
        $this->use_include_path =true;
        $this->php_handling =self::PHP_ALLOW;
        $this->allow_php_templates =true;

        // プラグイン読み込み設定
        $this->registerDefaultPluginHandler(array($this,"pluginHandler"));
        // @deprecated app/include/以下の関数による定義の探索
        $this->addPluginsDir("modules/smarty_plugin/");

        // キャッシュ/コンパイル済みデータ保存先設定
        $cache_dir =registry("Path.tmp_dir").'/smarty_cache/';
        $this->setCacheDir($cache_dir);
        $this->setCompileDir($cache_dir);
        if ( ! file_exists($cache_dir)) {
            mkdir($cache_dir,0775);
        }
    }

    /**
     * Smarty::registerDefaultPluginHandlerに登録するメソッド
     * プラグイン読み込み処理
     */
    public function pluginHandler ($name, $type, $template, &$callback, &$script, &$cacheable)
    {
        $result = extention("SmartyPlugin",$type.".".$name);
        if (is_callable($result)) {
            $callback = $result;
            return true;
        }
        return false;
    }

    /**
     * 現在のForm領域を取得
     */
    public function getCurrentForm ()
    {
        return $this->form_stack_top;
    }
    /**
     * Form領域を設定する
     */
    public function setCurrentForm ($form)
    {
        $this->form_stack[] = $this->form_stack_top;
        $this->form_stack_top = $form;
    }
    /**
     * Form領域を解除する
     */
    public function removeCurrentForm ()
    {
        $this->form_stack_top = array_pop($this->form_stack);
    }
}
