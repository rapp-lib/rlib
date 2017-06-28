<?php
namespace R\Lib\Http;
use Psr\Http\Message\UriInterface;

class Uri extends \Zend\Diactoros\Uri
{
    protected $webroot;
    protected $parsed;
    public function __construct ($webroot, $uri, $query_params=array(), $fragment="")
    {parent::__construct("".$uri);
        // Webrootの設定
        $this->webroot = $webroot;
        // URL文字列を元に初期化
        if (is_array($uri) && isset($uri["page_id"])) {
            $uri = self::buildUriByPageId($uri["page_id"], $uri["embed_params"]);
            parent::__construct($uri);
        } elseif (is_array($uri) && isset($uri["page_path"])) {
            $uri = self::buildUriByPagePath($uri["page_path"]);
            parent::__construct($uri);
        } elseif (is_string($uri) && preg_match('!^id://([\i+\.]+)(\?.*)?$!', $uri, $match)) {
            $page_id = $match[1];
            $embed_params = $match[2] ? parse_str($match[2]) : array();
            $uri = self::buildUriByPageId($page_id, $embed_params);
            parent::__construct($uri);
        } elseif (is_string($uri) && preg_match('!^path:///?(.*)$!', $uri, $match)) {
            $uri = self::buildUriByPagePath("/".$match[1]);
            parent::__construct($uri);
        // 一般的なUriInterfaceをもとに初期化
        } elseif (is_string($uri) || $uri instanceof UriInterface) {
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
    public function getPageFile()
    {
        $this->initParsed();
        return $this->parsed["page_file"];
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

// --

    public function __report()
    {
        return array("uri_string"=>"".$this, "parsed"=>$this->parsed);
    }
}
