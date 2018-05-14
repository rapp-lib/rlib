<?php
namespace R\Lib\Doc\Writer;

abstract class Writer_Base
{
    abstract public function write($file);

    protected $content;
    public function __construct($content)
    {
        $this->content = $content;
    }
    public function touchFile($file)
    {
        $dir = dirname($file);
        if ( ! is_dir($dir)) mkdir($dir, 0777, true);
        touch($file);
        chmod($file, 0777);
        return $file;
    }
}
