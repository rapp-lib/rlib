<?php
namespace R\Lib\Doc\Content;

class Content_Base
{
    protected $data;
    public function __construct($data=null)
    {
        $this->data = $data;
    }
    public function getData()
    {
        return $this->data;
    }
    public function setData($data)
    {
        $this->data = $data;
    }
    public function write($filename)
    {
        return false;
    }
    public function read($filename)
    {
        return false;
    }
}
