<?php
namespace R\Lib\Doc\Content;
use R\Lib\Builder\SchemaCsvLoader;
use R\Lib\Doc\Writer\SchemaCsvWriter;

class SchemaCsv extends Content_Base
{
    public function write($filename)
    {
        $writer = new SchemaCsvWriter();
        $writer->write($filename, $this->data);
    }
    public function read($filename)
    {
        $loader = new SchemaCsvLoader();
        $this->data = $loader->load($filename);
    }
}
