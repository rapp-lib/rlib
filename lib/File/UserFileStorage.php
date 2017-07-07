<?php
namespace R\Lib\File;

class UserFileStorage
{
    private $storage_name;
    private $config;
    public function __construct($storage_name, $config)
    {
        $this->storage_name = $storage_name;
        $this->config = $config;
    }
    public function getFile($params)
    {
        $params["storage"] = $this->getName();
        $params = call_user_func($this->config["params_filter"], $this, $params);
        if ( ! isset($params["id"])) {
            $params["id"] = $this->replace($this->config["id"], $params);
        }
        $params["uri"] = $this->replace($this->config["uri"], $params);
        $params["source"] = $this->replace($this->config["source"], $params);
        return new UserFile($params);
    }
    public function getFileByUri($uri)
    {
        $params = call_user_func($this->config["uri_parser"], $this, $uri);
        return $params ? $this->getFile($params) : null;
    }
    public function getName()
    {
        return $this->storage_name;
    }
    /**
     * パラメータの置き換え
     */
    public function replace($string, $params)
    {
        $string = preg_replace_callback('!\{([^\}]+)\}!', function($match)use($params){
            return isset($params[$match[1]]) ? $params[$match[1]] : "{}";
        }, $string);
        return strpos($string, '{}')===false ? $string : null;
    }
    /**
     * ファイルアップロード
     */
    public function upload($uploaded_file)
    {
        $file = $this->getFile(array("filename"=>$uploaded_file->getClientFilename()));
        $stream = $file->getSource();
        if (preg_match('!^(file:/)?/!', $stream)) {
            if ( ! file_exists(dirname($stream))) mkdir(dirname($stream), 0777, true);
            $uploaded_file->moveTo($stream);
        } else {
            $uploaded_file->writeFile($stream);
        }
        return $file;
    }
}
