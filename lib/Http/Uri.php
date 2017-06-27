<?php
namespace R\Lib\Http;
use Psr\Http\Message\UriInterface;

class Uri extends \Zend\Diactoros\Uri
{
    protected $webroot;
    protected $parsed;
    public function __construct ($webroot, $uri, $query_params=array(), $fragment="")
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
            parent::__construct("".$uri);
        } else {
            report_error("不正な引数", array(
                "uri" => $uri,
            ));
        }
    }
    public function getWebroot()
    {
        return $this->webroot;
    }
    public function getPageId()
    {
        $this->initParsed();
        return $this->parsed["page_id"];
    }
    public function getEmbedParams()
    {
        $this->initParsed();
        return $this->parsed["embed_params"];
    }
    public function getPagePath()
    {
        $this->initParsed();
        return $this->parsed["page_path"];
    }
    public function getPageAction()
    {
        $this->initParsed();
        return $this->parsed["page_action"];
    }
    private function initParsed()
    {
        if ( ! isset($this->parsed)) {
            $this->parsed = $this->webroot->getRouter()->parseUri($this);
        }
    }
}
