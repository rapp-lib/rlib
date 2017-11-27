<?php
namespace R\Lib\Builder;

use R\Lib\Builder\Element\SchemaElement;

class WebappBuilder extends SchemaElement
{
    public function createSchema ()
    {
        return new SchemaElement();
    }
    public function parseSchemaCsv ($schema_csv_file)
    {
        $schema_loader = new SchemaCsvLoader;
        $schema_data = $schema_loader->load($schema_csv_file);
        return $schema_data;
    }
}
