<?php
namespace R\Lib\Builder\Element;

use R\Lib\Builder\SchemaCsvLoader;

class SchemaElement
{
    /**
     * CSVファイルを読み込んで初期化
     */
    public function initFromSchemaCsv ($schema_csv_file)
    {
        $loader = new SchemaCsvLoader;
        $schema = $loader->load($schema_csv_file);
        $controllers = $schema["controller"];
        $tables = $schema["tables"];
        // Role登録
        foreach ($controllers as $controller_name => $controller_attrs) {
            $role_name = $controller_attrs["access_as"];
            $role_attrs = array();
            $this->children["roles"][$role_name] = new RoleElement($role_name, $role_attrs, $this);
        }
        // Controller登録
        foreach ($controllers as $controller_name => $controller_attrs) {
            $this->children["controllers"][$controller_name] = new ControllerElement($controller_name, $controller_attrs, $this);
        }
        // Table登録
        foreach ($tables as $table_name => $table_attrs) {
            $this->children["tables"][$table_name] = new TableElement($table_name, $table_attrs, $this);
        }
    }
    /**
     * @getter Controllers
     */
    public function getController ($name=false)
    {
        if ($name===false) {
            report_warning("@deprecated");
            return $this->getControllers();
        }
        return $this->children["controllers"][$name];
    }
    public function getControllers ()
    {
        return (array)$this->children["controllers"];
    }
    /**
     * @getter Tables
     */
    public function getTable ($name=false)
    {
        if ($name===false) {
            report_warning("@deprecated");
            return $this->getTables();
        }
        return $this->children["tables"][$name];
    }
    public function getTables ()
    {
        return (array)$this->children["tables"];
    }
    /**
     * @getter Roles
     */
    public function getRole ($name=false)
    {
        if ($name===false) {
            report_warning("@deprecated");
            return $this->getRoles();
        }
        return $this->children["roles"][$name];
    }
    public function getRoles ()
    {
        return (array)$this->children["roles"];
    }
}
