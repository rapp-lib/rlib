<?php
namespace R\Lib\Doc\Writer;

abstract class Writer
{
    protected $content;
    public function __construct($content)
    {
        $this->content = $content;
    }
    abstract public function write($filename);
}
