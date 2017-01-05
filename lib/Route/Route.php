<?php
namespace R\Lib\Route;

/**
 *
 */
class Route
{
    private $webroot;
    private $path = null;
    private $page = null;
    private $url_params = array();
    /**
     *
     */
    public function __construct ($webroot, $route_name)
    {
        $this->webroot = $webroot;
        // "path:","/"で始まる場合Pathと判断する
        if (preg_match('!^(?:path:)?(/.*)$!',$route_name,$match)) {
            $this->path = $match[1];
        // "page:","XXX.XXX",".XXX"形式である場合Pageと判断する
        } elseif (preg_match('!^(?:page:)?([a-zA-Z0-9_]+)?\.([a-zA-Z0-9_]+)?$!',$route_name,$match)) {
            $controller_name = $match[1];
            $action_name = $match[2];
            // 相対Pageで記述されている場合、現在アクセスしているPageから補完
            if (strlen($controller_name)==0) {
                $current_page = $this->getWebroot()->getRouteManager()->getCurrentRoute()->getPage();
                $controller_name = preg_replace('!\.[^\.]+$!', '', $current_page);
                if (strlen($action_name)==0) {
                    $action_name = preg_replace('!^[^\.]+\.!', '', $current_page);
                }
            }
            $this->page = $controller_name.".".$action_name;
        // "url:"で始まる場合URLと判断する
        } elseif (preg_match('!^(?:url:)(.*)$!',$route_name,$match)) {
            $url = $match[1];
            list($this->path, $this->url_params, $path_matched) = $this->getWebroot()->parseUrl($url);
            // 非パラメータ埋め込みでパターン一致を含むPathであればPage/Pathを独立にする
            if ($path_matched && ! $this->url_params) {
                $this->page = $this->getWebroot()->pathToPage($this->path);
                $this->path = $path_matched;
            }
        // "file:"で始まる場合ファイル名と判断する
        } elseif (preg_match('!^(?:file:)(.*)$!',$route_name,$match)) {
            $file = $match[1];
            $docroot_dir = $this->webroot->getAttr("docroot_dir",true);
            $webroot_dir = $docroot_dir.$this->webroot->getAttr("webroot_url",true);
            // 先頭のdocroot_dir+webroot_urlを削る
            if (strpos($webroot_dir, $file)===0) {
                $this->path = str_replace($webroot_dir, "", $file);
            // 変換できない領域のファイルであればエラー
            } else {
                report_warning("Webroot外のファイルはRouteを定義できません",array(
                    "webroot" => $this->webroot,
                    "file" => $file,
                ));
            }
        }
    }
    /**
     * @getter
     */
    public function getWebroot ()
    {
        return $this->webroot;
    }
    /**
     *
     */
    public function getPage ()
    {
        if ( ! isset($this->page) && isset($this->path)) {
            $this->page = $this->getWebroot()->pathToPage($this->path);
        }
        return $this->page;
    }
    /**
     *
     */
    public function getPath ()
    {
        if ( ! isset($this->path) && isset($this->page)) {
            $this->path = $this->getWebroot()->pageToPath($this->page);
        }
        return $this->path;
    }
    /**
     * URLパラメータを取得
     */
    public function getUrlParams ()
    {
        return $this->url_params;
    }
    /**
     * URLパラメータを設定
     */
    public function setUrlParams ($url_params)
    {
        $this->url_params = $url_params;
    }
    /**
     *
     */
    public function getFile ()
    {
        $path = $this->getPath();
        $url = strlen($path) ? $this->getWebroot()->getAttr("webroot_url",true).$path : null;
        return strlen($url) ? $this->getWebroot()->getAttr("docroot_dir",true).$url : null;
    }
    /**
     *
     */
    public function getUrl ()
    {
        $path = $this->getPath();
        // 埋め込みパラメータの置換
        if (isset($this->url_params)) {
            foreach ($this->url_params as $key => $value) {
                $path = str_replace('['.$key.']',$value,$path);
            }
        }
        return strlen($path) ? $this->getWebroot()->getAttr("webroot_url",true).$path : null;
    }
    /**
     *
     */
    public function getFullUrl ()
    {
        $url = $this->getUrl();
        if ( ! strlen($url)) {
            return null;
        }
        $schema = $this->getWebroot()->getAttr("is_secure") ? "https:" : "http:";
        $url = $schema."//".$this->getWebroot()->getAttr("domain_name",true).$url;
        return $url;
    }
    /**
     * @deprecated
     */
    public function __report ()
    {
        return array(
            "path" => $this->getPath(),
            "page" => $this->getPage(),
            "webroot" => $this->getWebroot(),
        );
    }
}
