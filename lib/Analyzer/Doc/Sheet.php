<?php
namespace R\Lib\Analyzer\Doc;

class Sheet
{
    private $doc;
    private $name;
    public function __construct($doc, $name)
    {
        $this->doc = $doc;
        $this->name = $name;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getDoc()
    {
        return $this->doc;
    }
    public function getBaseFilename()
    {
        $doc = $sheet->getDoc();
        $shelf = $sheet->getShelf();
        return $shelf->getDir()."/".$doc->getName()."/".$this->getName();
    }

// -- header

    protected $header = array();
    protected $map = array();
    public function setHeader($header)
    {
        $this->header = array_values($header);
        $this->map = array_keys($header);
    }

// -- line

    protected $lines = array();
    public function addLine($line)
    {
        if ($this->map) {
            $mapped_line = array();
            foreach ($this->map as $i=>$k) $mapped_line[$i] = $line[$k];
            $this->lines[] = $mapped_line;
        } else $this->lines[] = $line;
    }

// -- table

    public function getTable()
    {
        $table = array();
        $table[] = (array)$this->header;
        foreach ($this->lines as $line) $table[] = $line;
        return $table;
    }
    public function setTable($table)
    {
        $this->setHeader(array_shift($table));
        $this->lines = $table;
    }

// -- render

    public static function preview ()
    {
        return DocRenderer::sheetPreview($this);
    }
    public static function save ($format)
    {
        return DocRenderer::sheetSave($this, $format);
    }
    public static function load ($format)
    {
        return DocRenderer::sheetLoad($this, $format);
    }
}
