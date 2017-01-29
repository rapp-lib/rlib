<?php
namespace R\Lib\View\Smarty;

use SmartyBC;

class SmartyExtended extends SmartyBC
{
    /**
     * Form領域の状態スタック
     */
    protected $form_stack_top = null;
    protected $form_stack = array();

    public function __construct ()
    {
        parent::__construct();
        // テンプレート基本設定
        $this->left_delimiter = '{{';
        $this->right_delimiter = '}}';
        $this->use_include_path = false;
        $this->php_handling = self::PHP_ALLOW;
        $this->allow_php_templates = true;
        // プラグイン読み込み設定
        $this->registerDefaultPluginHandler(array($this,"pluginHandler"));
        // 関数名と衝突するプラグインの事前登録
        $this->registerPlugin("modifier","enum",extention("SmartyPlugin", "modifier.enum"));
        $this->registerPlugin("modifier","date",extention("SmartyPlugin", "modifier.date"));
        // @deprecated app/include/以下の関数による定義の探索
        $this->addPluginsDir("modules/smarty_plugin/");
        // キャッシュ/コンパイル済みデータ保存先設定
        $cache_dir = constant("R_APP_ROOT_DIR").'/tmp/smarty_cache/';
        $this->setCacheDir($cache_dir);
        $this->setCompileDir($cache_dir);
        if ( ! file_exists($cache_dir)) {
            mkdir($cache_dir,0775,true);
        }
    }
    /**
     * Smarty::registerDefaultPluginHandlerに登録するメソッド
     * プラグイン読み込み処理
     */
    public function pluginHandler ($name, $type, $template, &$callback, &$script, &$cacheable)
    {
        $extention = extention("SmartyPlugin", $type.".".$name);
        if (is_callable($extention)) {
            $callback = $extention;
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
    /**
     * @override Smarty_Internal_TemplateBase
     */
    public function fetch ($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        report_buffer_start();
        try {
            $result = parent::fetch($template, $cache_id, $compile_id, $parent);
        } catch (\Exception $e) {
            report_buffer_end();
            throw $e;
        }
        report_buffer_end();
        return $result;
    }
}
