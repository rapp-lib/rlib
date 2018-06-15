<?php
namespace R\Lib\Form;

class UploadedFile
{
    protected $uri = null;
    public function __construct($uri, $file)
    {
        $this->uri = $uri;
        $this->file = $file;
    }
    public function getUri()
    {
        return $this->uri;
    }
    public function getFile()
    {
        return $this->file;
    }
    public function __toString()
    {
        return "".$this->getUri();
    }
}
