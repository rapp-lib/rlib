<?php
namespace R\Lib\Http;
use Psr\Http\Message\UriInterface;

class Uri extends \Zend\Diactoros\Uri
{
    protected $webroot;
    public function __construct ($webroot, $uri, $query_params=false, $fragment=false)
    {
        // Webrootの設定
        $this->webroot = $webroot;
        // URL文字列を元に初期化
        if (is_string($uri)) {
            parent::__construct($uri);
        } elseif (is_array($uri) && isset($uri["page_id"])) {
            $uri = self::buildUriByPageId($uri["page_id"], $uri["embed_params"]);
            parent::__construct($uri);
        } elseif (is_array($uri) && isset($uri["page_path"])) {
            $uri = self::buildUriByPagePath($uri["page_path"]);
            parent::__construct($uri);
        // 一般的なUriInterfaceをもとに初期化
        } elseif ($uri instanceof UriInterface) {
            $uri = \Zend\Diactoros\Uri::createUriString($uri->getScheme(),
                $uri->getAuthority(), $uri->getPath(), $uri->getQuery(), $uri->getFragment());
            parent::__construct($uri);
        } else {
            report_error("不正な引数");
        }
    }
    public function getWebroot()
    {
        return $this->webroot;
    }
    public function getPageId()
    {
        return $this->page_id;
    }
    public function getPagePath()
    {
        return $this->page_path;
    }
}
