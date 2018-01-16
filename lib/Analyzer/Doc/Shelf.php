<?php
namespace R\Lib\Analyzer\Doc;

class Shelf
{
    private $dir;
    public function __construct($dir)
    {
        $this->dir = $dir;
    }
    public function getDir()
    {
        return $this->dir;
    }

// -- doc

    private $docs = array();
    public function getDoc($name)
    {
        if ( ! $this->docs[$name]) $this->docs[$name] = new Doc($this, $name);
        return $this->docs[$name];
    }
    public function getDocs()
    {
        return $this->docs;
    }
}
