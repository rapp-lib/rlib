<?php
namespace R\Lib\Analyzer;

use R\Lib\Analyzer\Def\SchemaDef;

class WebappAnalyzer
{
    protected $schema = null;
    public function getSchema()
    {
        if ( ! $this->schema) $this->schema = new SchemaDef();
        return $this->schema;
    }
}
