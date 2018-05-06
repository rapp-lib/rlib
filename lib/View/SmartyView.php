<?php
namespace R\Lib\View;

use \SmartyBC;

class SmartyView
{
    public function fetch ($template_file, $vars=array(), $options=array())
    {
        if ( ! is_file($template_file) || ! is_readable($template_file)) {
            report_error("Smartyテンプレートファイルが読み込めません",array(
                "template_file" => $template_file,
            ));
        }
        $smarty = $this->makeSmarty();
        // キャッシュDIRの初期化
        $cache_dir = $smarty->getCompileDir();
        if ( ! file_exists($cache_dir)) mkdir($cache_dir,0775,true);
        // 変数のアサイン/Fetch
        $smarty->assign((array)$vars);
        return $smarty->fetch($template_file);
    }

// -- Smarty

    /**
     * SmartyオブジェクトのFactory
     */
    protected function makeSmarty ()
    {
        $smarty = new SmartyBC();
        // テンプレート基本設定
        $smarty->left_delimiter = '{{';
        $smarty->right_delimiter = '}}';
        $smarty->use_include_path = false;
        $smarty->php_handling = SmartyBC::PHP_ALLOW;
        $smarty->allow_php_templates = true;
        $smarty->escape_html = true;
        // プラグイン読み込み設定
        $smarty->registerDefaultPluginHandler(array($this,"pluginHandler"));
        // 関数名と衝突するプラグインの事前登録
        $smarty->registerPlugin("modifier", "date", "str_date");
        $smarty->registerPlugin("modifier", "url", get_class($this)."::smarty_modifier_url");
        // キャッシュ/コンパイル済みデータ保存先設定
        $cache_dir = constant("R_APP_ROOT_DIR").'/tmp/smarty_cache/';
        $smarty->setCacheDir($cache_dir);
        $smarty->setCompileDir($cache_dir);
        return $smarty;
    }
    /**
     * Smarty::registerDefaultPluginHandlerに登録するメソッド
     * プラグイン読み込み処理
     */
    public function pluginHandler ($name, $type, $template, &$callback, &$script, &$cacheable)
    {
        $callback_tmp = get_class($this)."::smarty_".$type."_".$name;
        if (is_callable($callback_tmp)) {
            $callback = $callback_tmp;
            return true;
        }
        return false;
    }
    /**
     * Smartyプラグイン内で上位のBlock要素の属性パラメータを取得する
     */
    public static function getClosestBlock ($smarty, $tag_name)
    {
        foreach ($smarty->smarty->_cache['_tag_stack'] as $block) {
            if ($block[0] === $tag_name) return $block[1];
        }
        return null;
    }
    /**
     * Path、Page文字列をURLに変換する
     */
    protected static function convertLooseUri ($string)
    {
        $request_uri = app()->http->getServedRequest()->getUri();
        if (is_string($uri)) {
            if (preg_match('!^([\w\d]+)?\.[\w\d]+!', $uri)) $uri = "id://".$uri;
            return $request_uri->getRelativeUri($uri);
        }
        return $request_uri->getRelativeUri();
    }

// -- Asset

    protected $assets = null;
    protected $repo_path = "path://.assets";
    public function getAssets ()
    {
        if ( ! $this->assets) {
            $this->assets = new FrontAssets();
            $uri = app()->http->getServedRequest()->getUri()->getWebroot()->uri($this->repo_path);
            $this->assets->addRepo($uri);
        }
        return $this->assets;
    }

// -- 基本処理プラグイン

    public static function smarty_modifier_trunc ($value, $length, $append="...")
    {
        if (mb_strlen($value,"UTF-8")>$length) $value = mb_substr($value,0,$length,"UTF-8").$append;
        return $value;
    }
    public static function smarty_modifier_map ($values, $array, $glue=false)
    {
        $res = array();
        if ($values) {
            if (is_string($array)) $array = app()->enum[$array];
            if ($array instanceof \R\Lib\Enum\EnumValueRepositry) $res = $array->map($values);
            else foreach ($values as $v) if (isset($array[$v])) $res[$v] = $array[$v];
        }
        return $glue===false ? $res : implode($glue, $res);
    }

// -- フォーム構築プラグイン

    public static function smarty_block_form ($attrs, $content, $smarty_template, $repeat)
    {
        // 開タグでは処理しない
        if ($repeat) return;
        $form = $attrs["form"];
        unset($attrs["form"]);
        if ( ! $form) report_error("formの指定は必須です");
        $html = $form->getFormHtml($attrs, $content);
        return $html;
    }
    public static function smarty_function_input ($attrs, $smarty)
    {
        $form = $attrs["form"];
        $assign = $attrs["assign"];
        unset($attrs["form"], $attrs["assign"]);
        if ( ! $form) {
            $form_attrs = self::getClosestBlock($smarty, "form");
            if ($form_attrs["form"]) $form = $form_attrs["form"];
        }
        if ( ! $form) report_error("{{input}}は{{form}}内でのみ有効です", array("attrs"=>$attrs));
        $input_field = $form->getInputField($attrs);
        // assignが指定されている場合、分解したHTMLを変数としてアサイン
        if ($assign) $smarty->assign($assign, $input_field);
        else return $input_field->getHtml();
    }
    public static function smarty_block_input_fieldset ($attrs, $content, $smarty, &$repeat)
    {
        $fieldset_name = $attrs["name"];
        $tmpl = $attrs["tmpl"] ?: null; // tmplに登録する際のkeyの値
        $key_assign = $attrs["key"] ?: "key"; // keyをアサインする変数名
        $assign = $attrs["assign"] ?: "fieldset"; // 結果をアサインする変数名
        $length = $attrs["length"] ?: null; // 固定数表示の場合の件数指定
        // Blockタグスタック上の情報を参照
        $stack_last_index = end($keys = array_keys($smarty->smarty->_cache['_tag_stack']));
        $tag = & $smarty->smarty->_cache['_tag_stack'][$stack_last_index];
        // 初回の開くタグの処理
        if ($repeat===true) {
            // Keysの初期化
            $form_attrs = self::getClosestBlock($smarty, "form");
            $form = $form_attrs["form"];
            $tag["keys"] = array();
            if ($length) $tag["keys"] = range(0,$length-1);
            elseif (isset($form[$fieldset_name])) $tag["keys"] = array_keys((array)$form[$fieldset_name]);
            // テンプレート処理用の要素をアサイン
            if (strlen($tmpl)) {
                $tag["current"] = "tmpl";
                $smarty->assign($key_assign, $tmpl);
            } else {
                $repeat = false;
            }
        }
        // 閉じタグ兼2周目以降の開くタグの処理
        if ($repeat===false) {
            // 処理した要素の出力をAssignに追加
            if ($tag["current"] === "tmpl") $tag["assign"]["tmpl"] = $content;
            elseif (strlen($tag["current"])) $tag["assign"]["items"][$tag["current"]] = $content;
            // Keysの残りがある限りループ処理
            if (count($tag["keys"])) {
                $smarty->assign($key_assign, $tag["current"] = array_shift($tag["keys"]));
                $repeat = true;
            // 全てのループ完了時にassignをおこなう
            } else {
                $smarty->assign($assign, $tag["assign"]);
            }
        }
    }

// -- Include処理プラグイン

    public static function smarty_function_inc ($params, $smarty)
    {
        // URLの解決
        $uri = $params["uri"];
        if ( ! $uri && $params["route"]) $uri = "path://".$params["route"];
        if ( ! $uri && $params["path"]) $uri = "path://".$params["path"];
        if ( ! $uri && $params["page"]) $uri = "id://".$params["page"];
        $request = app()->http->getServedRequest();
        $uri = $request->getUri()->getRelativeUri($uri);
        // テンプレートのFetch
        $smarty_sub = clone($smarty);
        $file = $uri->getPageFile();
        if ( ! is_file($file)) {
            report_warning("incタグの対象となるファイルがありません",array(
                "file" => $file,
                "uri" => $uri,
            ));
        }
        // actの呼び出し
        if ($uri->getPageId()) {
            $result = $uri->getPageController()->invokeAction($request);
            $smarty_sub->assign((array)$result["vars"]);
        }
        return $smarty_sub->fetch($file);
    }

// -- URL解決プラグイン

    public static function smarty_modifier_url ($url, $query_params=array(), $anchor=null)
    {
        return "".app()->http->getServedRequest()->getUri()
            ->getRelativeUri($url, $query_params, $anchor)
            ->withToken()->withoutAuthorityInWebroot();
    }
    public static function smarty_modifier_page_to_url ($page_id, $query_params=array(), $anchor=null)
    {
        return "".app()->http->getServedRequest()->getUri()
            ->getRelativeUri("id://".$page_id, $query_params, $anchor)
            ->withToken()->withoutAuthorityInWebroot();
    }
    public static function smarty_modifier_path_to_url ($path, $url_params=array(), $anchor=null)
    {
        return "".app()->http->getServedRequest()->getUri()
            ->getRelativeUri("path://".$path, $query_params, $anchor)
            ->withToken()->withoutAuthorityInWebroot();
    }

// -- 認証解決プラグイン

    public static function smarty_modifier_is_accessible ($uri)
    {
        if (is_string($uri) && preg_match('!^([\w\d]+)?\.[\w\d]+!', $uri)) $uri = "id://".$uri;
        $page_auth = app()->http->getServedRequest()->getUri()
            ->getRelativeUri($uri)->getPageAuth();
        return app()->user->checkCurrentPriv($page_auth->getRole(), $page_auth->getPrivReq());
    }
    public static function smarty_modifier_get_priv ($priv_req, $role=null)
    {
        if ( ! $role) $role = app()->http->getServedRequest()->getUri()->getPageAuth()->getRole();
        $priv = app()->user->getCurrentPriv($role);
        return $priv[$priv_req];
    }

// -- Assets処理プラグイン

    public static function smarty_block_script ($attrs, $content, $smarty, &$repeat)
    {
        if ( ! $repeat) {
            if ($attrs["require"]) app()->view()->getAssets()->load($attrs["require"]);
            if ($attrs["src"]) app()->view()->getAssets()->scriptUri($attrs["src"]);
            if ($attrs["loaded"]) app()->view()->getAssets()->loaded($attrs["loaded"]);
            if (strlen($content)) app()->view()->getAssets()->script($content);
        }
    }
    public static function smarty_function_render_assets ($attrs, $smarty)
    {
        return app()->view()->getAssets()->render(array("clear"=>true));
    }

// -- FlashMessageプラグイン

    public static function smarty_modifier_flash ($options=array())
    {
        $flash = app()->session->getFlash()->get($options);
        return $flash;
    }
}
