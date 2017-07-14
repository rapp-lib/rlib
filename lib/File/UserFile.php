<?php
namespace R\Lib\File;

class UserFile
{
    private $params;
    public function __construct($params)
    {
        // ディレクトリトラバーサル対策
        foreach ($params as & $v) $v = str_replace(array('/../',"\0"), '', $v);
        $this->params = $params;
    }
    public function getId()
    {
        return $this->params["id"];
    }
    public function getUri()
    {
        return $this->params["uri"];
    }
    public function getSource()
    {
        // "s3://"はAmazon S3 stream wrapperを導入して解決
        return $this->params["source"];
    }

// --

    public function __report()
    {
        return $this->params;
    }
}
