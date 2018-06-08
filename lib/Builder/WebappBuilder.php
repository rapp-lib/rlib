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
    public function build ($config)
    {
        // SchemaElementを作成
        $schema = $this->createSchema();
        // CSV読み込み
        $schema_data = $this->parseSchemaCsv($config["schema_csv_file"]);
        $schema->addSkel($config["skel_dir"]);
        $schema->loadSchemaData($schema_data);
        // 自動生成の実行
        $schema->registerDeployCallback($config["deploy_callback"]);
        $schema->deploy(true);
    }
}
