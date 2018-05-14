<?php
namespace R\Lib\Doc\Format;
use R\Lib\Analyzer\Def\SchemaDef;

abstract class Format_Base
{
    abstract public function getContents();
    private $schema;
    protected function getSchema()
    {
        if ( ! $this->schema) $this->schema = new SchemaDef();
        return $this->schema;
    }
}
