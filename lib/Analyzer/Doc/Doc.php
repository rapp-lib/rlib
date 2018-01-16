<?php
namespace R\Lib\Analyzer\Doc;

class Doc
{
    private $shelf;
    private $name;
    public function __construct($shelf, $name)
    {
        $this->shelf = $shelf;
        $this->name = $name;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getShelf()
    {
        return $this->shelf;
    }

// -- sheet

    private $sheets = array();
    public function getSheet($name)
    {
        if ( ! $this->sheets[$name]) $this->sheets[$name] = new Sheet($this, $name);
        return $this->sheets[$name];
    }
    public function getSheets()
    {
        return $this->sheets;
    }
}