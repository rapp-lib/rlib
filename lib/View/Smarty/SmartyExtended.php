<?php
namespace R\Lib\View\Smarty;

use SmartyBC;

class SmartyExtended extends SmartyBC
{
    /**
     * Blockタグの状態スタック
     */
    protected $block_tag_stack = array();

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
        $this->registerPlugin("modifier","date",extention("SmartyPlugin", "modifier.date"));
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

// --

    /**
     * Blockの状態を管理するStackを取得する
     */
    public function getBlockTagStack ($stack_name)
    {
        if ( ! isset($this->block_tag_stack[$stack_name])) {
            $this->block_tag_stack[$stack_name] = new BlockTagStack();
        }
        return $this->block_tag_stack[$stack_name];
    }
    /**
     * 現在のForm領域を取得
     */
    public function getCurrentForm ()
    {
        return $this->getBlockTagStack("form")->top();
    }
    /**
     * Form領域を設定する
     */
    public function setCurrentForm ($form)
    {
        $this->getBlockTagStack("form")->push($form);
    }
    /**
     * Form領域を解除する
     */
    public function removeCurrentForm ()
    {
        return $this->getBlockTagStack("form")->pop();
    }

// --

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

// --

    public function __report ()
    {
        return;
    }
}

class BlockTagStack
{
    private $top = null;
    private $stack = array();
    /**
     *
     */
    public function top ()
    {
        return $this->top;
    }
    /**
     *
     */
    public function push ($item)
    {
        array_push($this->stack, $item);
        $this->top = $item;
    }
    /**
     *
     */
    public function pop ()
    {
        $this->top = array_pop($this->stack);
        return $this->top;
    }
}
