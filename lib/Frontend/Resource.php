<?php
/**
 *
 */
namespace R\Lib\Frontend;

/**
 *
 */
class Resource
{
    private $resource_manager;
    private $data;
    private $data_type;

    private $module_name = null;
    private $module_version = null;

    private $dependencies = array();
    private $attrs = array();

    /**
     * @override
     */
    public function __construct ($resource_manager, $data, $data_type)
    {
        $this->resource_manager = $resource_manager;
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
            $html .= "\n".$this->resource_manager->load($module_name, $required_version)."\n";
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
            $code_lines = is_array($this->data) ? $this->data :array($this->data);
            $code = "\n".implode("\n",$code_lines)."\n";
            // scriptタグの組み立て
            $attrs = $this->attrs;
            $html = tag('script',$attrs,$code);

        // css_url
        } elseif ($this->data_type=="css_url") {
            // linkタグの組み立て
            $attrs = $this->attrs;
            $attrs["href"] = $this->data;
            $attrs["rel"] = "stylesheet";
            $attrs["type"] = "text/css";
            $html = tag('link',$attrs,"");

        // css_code
        } elseif ($this->data_type=="css_code") {
            // コードの改行
            $code_lines = is_array($this->data) ? $this->data :array($this->data);
            $code = "\n".implode("\n",$code_lines)."\n";
            // styleタグの組み立て
            $attrs = $this->attrs;
            $attrs["type"] = "text/css";
            $html = tag('style',$attrs,$code);
        }
        return $html;
    }
}
