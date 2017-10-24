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
        $smarty->registerPlugin("modifier", "date", get_class($this)."::smarty_modifier_date");
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
        $callback_tmp = \R\Lib\Extention\SmartyPluginLoader::getCallback($type.".".$name);
        if (is_callable($callback_tmp)) {
            $callback = $callback_tmp;
            return true;
        }
        $callback_tmp = get_class($this)."::smarty_".$type."_".$name;
        if (is_callable($callback_tmp)) {
            $callback = $callback_tmp;
            return true;
        }
        return false;
    }

// -- 基本処理プラグイン

    public static function smarty_modifier_date ($string ,$format="Y/m/d")
    {
        if ( ! strlen($string)) return "";
        $date = new \DateTime($string);
        return $date->format($format);
    }
    public static function smarty_modifier_trunc ($value, $length, $append="...")
    {
        if (mb_strlen($value,"UTF-8")>$length) $value = mb_substr($value,0,$length,"UTF-8").$append;
        return $value;
    }

// -- フォーム構築プラグイン

    public static function smarty_block_form ($attrs, $content, $smarty_template, $repeat)
    {
        $form = $attrs["form"];
        unset($attrs["form"]);
        if ( ! $form) report_error("formの指定は必須です");
        // 閉タグでHTML出力
        if ( ! $repeat) return $form->getFormHtml($attrs, $content);
    }
    public static function smarty_function_input ($attrs, $smarty)
    {
        $form = $attrs["form"];
        $assign = $attrs["assign"];
        unset($attrs["form"], $attrs["assign"]);
        if ( ! $form) foreach ($smarty->smarty->_cache['_tag_stack'] as $block) {
            if ($block[0] === "form" && $block[1]["form"]) $form = $block[1]["form"];
        }
        if ( ! $form) report_error("{{input}}は{{form}}内でのみ有効です", array("attrs"=>$attrs));
        $input_field = $form->getInputField($attrs);
        // assignが指定されている場合、分解したHTMLを変数としてアサイン
        if ($assign) $smarty->assign($assign, $input_field);
        else return $input_field->getHtml();
    }
    public static function smarty_block_input_field_set ($attrs, $content, $smarty, &$repeat)
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
            $form = $smarty->getCurrentForm();
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
        $uri = $params["uri"];
        if ( ! $uri && $params["route"]) $uri = "path://".$params["route"];
        if ( ! $uri && $params["path"]) $uri = "path://".$params["path"];
        if ( ! $uri && $params["page"]) $uri = "id://".$params["page"];
        $request = app()->http->getServedRequest();
        $uri = $request->getUri()->getWebroot()->uri($uri);
        // Routeに対応する処理の実行
        $vars = $uri->getPageAction()->runInternal($request);
        $request_file = $uri->getPageFile();
        if ( ! file_exists($request_file)) {
            report_warning("incタグの対象となるテンプレートファイルがありません",array(
                "request_file" => $request_file,
                "uri" => $uri,
            ));
            return;
        }
        // テンプレートの読み込み
        $smarty_clone = clone($smarty);
        $smarty_clone->assign($vars);
        return $smarty_clone->fetch($request_file);
    }

// -- URL解決プラグイン

    public function smarty_modifier_page_to_url ($page_id, $query_params=array(), $anchor=null)
    {
        $request_uri = app()->http->getServedRequest()->getUri();
        $page_id = $request_uri->getPageAction()->getController()->resolveRelativePageId($page_id);
        $uri = $request_uri->getWebroot()->uri(array("page_id"=>$page_id), $query_params, $anchor)
            ->withoutAuthorityInWebroot();
        return "".$uri;
    }
    public function smarty_modifier_path_to_url ($path, $url_params=array(), $anchor=null)
    {
        $uri = app()->http->getServedRequest()->getUri()
            ->getPageAction()->getController()->uri("path://".$path, $url_params, $anchor)->withoutAuthorityInWebroot();
        return "".$uri;
    }

// -- 認証解決プラグイン

    public function smarty_modifier_url_to_priv_req ($uri)
    {
        $uri = app()->http->getServedRequest()->getUri()->getWebroot()->uri($uri);
        $priv_req = $uri->getPageAuth()->getPrivReq();
        return $priv_req;
    }
    public function smarty_modifier_check_user_priv ($priv_req, $role=null)
    {
        $role = isset($role) ? $role : app()->user->getCurrentRole();
        return app()->user->checkCurrentPriv($role, $priv_req);
    }
}
