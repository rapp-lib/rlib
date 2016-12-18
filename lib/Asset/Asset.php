<?php
/**
 *
 */
namespace R\Lib\Asset;

/**
 *
 */
class Asset
{
    private static $data_types =array(
        "js_url" => array(),
        "js_code" => array(),
        "css_url" => array(),
        "css_code" => array(),
        "php_file" => array(),
        "php_require_file" => array(),
        "none" => array(),
    );

    private $asset_manager;
    private $data;
    private $data_type;

    private $module_name = null;
    private $module_version = null;

    private $dependencies = array();
    private $attrs = array();

    /**
     * @override
     */
    public function __construct ($asset_manager, $data, $data_type)
    {
        if ( ! isset(self::$data_types[$data_type])) {
            report_error("アセットタイプの指定が不正です",array(
                "data_type" => $data_type,
                "data" => $data,
            ));
        }
        $this->asset_manager = $asset_manager;
        $this->data = $data;
        $this->data_type = $data_type;
    }

    /**
     * @setter
     */
    public function setAttr ($key, $value)
    {
        $this->attrs[$key] = $value;
    }

    /**
     * @setter
     */
    public function setModuleName ($module_name, $version=null)
    {
        $this->module_name = $module_name;
        $this->version = $version;
    }

    /**
     * @setter
     */
    public function getVersion ()
    {
        return $this->version;
    }

    /**
     * 依存モジュールを登録する
     */
    public function required ($module_name, $required_version="*")
    {
        $this->dependencies[$module_name] = $required_version;
        return $this;
    }

    /**
     * 依存解決を行いHTMLを出力
     */
    public function getHtmlWithDepenedencies ()
    {
        $html = "";
        // 依存先のモジュールを読み込んで必要に応じてHTMLを取得
        foreach ($this->dependencies as $module_name => $required_version) {
            $html .= $this->asset_manager->load($module_name, $required_version);
        }
        if ($this->module_name) {
            $html .= "\n"."<!-- ".$this->module_name." ".$this->version." -->";
        } else {
        }
        $html .= "\n".$this->getHtml()."\n";
        return $html;
    }

    /**
     * HTML組み立て処理
     */
    public function getHtml ()
    {
        $html = "";
        // リソースタイプ別のHTML組み立て処理
        // js_url
        if ($this->data_type=="js_url") {
            // scriptタグの組み立て
            $attrs = $this->attrs;
            $attrs["src"] = $this->data;
            $html = tag('script',$attrs,"");

        // js_code
        } elseif ($this->data_type=="js_code") {
            // コードの改行
            $code_lines = is_array($this->data) ? $this->data : array($this->data);
            $code = "\n".implode("\n",$code_lines)."\n";
            // scriptタグの組み立て
            $attrs = $this->attrs;
            $html = tag('script',$attrs,$code);

        // css_url
        } elseif ($this->data_type=="css_url") {
            if ( ! $this->asset_manager->checkState("html.before","head.end") && ! $attr["async"]) {
                // linkタグの組み立て
                $attrs = $this->attrs;
                $attrs["href"] = $this->data;
                $attrs["rel"] = "stylesheet";
                $attrs["type"] = "text/css";
                $html = tag('link',$attrs,"");
            } else {
                // 属性の組み立て
                $attrs = $this->attrs;
                $attrs["href"] = $this->data;
                $attrs["rel"] = "stylesheet";
                $attrs["type"] = "text/css";
                $code_lines = array();
                // 非同期化
                if ($attr["async"]) {
                    $code_lines[] = 'window.onload=function(){';
                }
                // linkタグを動的に読み込むJSの組み立て
                $code_lines[] = 'var css=document.createElement("link");';
                foreach ($attrs as $k => $v) {
                    $code_lines[] = 'css.setAttribute("'.$k.'","'.$v.'");';
                }
                $code_lines[] = 'document.getElementsByTagName("head")[0].appendChild(css);';
                // 非同期化ブロックを閉じる
                if ($attr["async"]) {
                    $code_lines[] = '}';
                }
                // コードの改行
                $code = "\n".implode("\n",$code_lines)."\n";
                // scriptタグの組み立て
                $html = tag('script',array(),$code);
            }
        // css_code
        } elseif ($this->data_type=="css_code") {
            // コードの改行
            $code_lines = is_array($this->data) ? $this->data :array($this->data);
            $code = "\n".implode("\n",$code_lines)."\n";
            // styleタグの組み立て
            $attrs = $this->attrs;
            $attrs["type"] = "text/css";
            $html = tag('style',$attrs,$code);

        // php_file
        } elseif ($this->data_type=="php_file") {
            ob_start();
            include($this->data);
            $html = ob_get_clean();

        // php_require_file
        } elseif ($this->data_type=="php_require_file") {
            require_once($this->data);

        // none
        } elseif ($this->data_type=="none") {
            $html = "";
        }
        return $html;
    }
}
