<?php
namespace R\Lib\Builder\Element;

use R\Lib\Builder\SchemaCsvLoader;
use R\Lib\Builder\Element\Element_Base;

class SchemaElement extends Element_Base
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
        $cols = $schema["cols"];
        // Role登録
        foreach ($controllers as $controller_name => $controller_attrs) {
            $role_name = $controller_attrs["access_as"];
            $role_attrs = array();
            $this->children["role"][$role_name] = new RoleElement($role_name, $role_attrs, $this);
        }
        // Table登録
        foreach ($tables as $table_name => $table_attrs) {
            $table_attrs["cols"] = (array)$cols[$table_name];
            $this->children["table"][$table_name] = new TableElement($table_name, $table_attrs, $this);
        }
        // Controller登録
        foreach ($controllers as $controller_name => $controller_attrs) {
            $this->children["controller"][$controller_name] = new ControllerElement($controller_name, $controller_attrs, $this);
        }
    }
    /**
     * @override
     */
    public function getSchema ()
    {
        return $this;
    }
    /**
     * @getter Controllers
     */
    public function getControllers ()
    {
        return (array)$this->children["controller"];
    }
    /**
     * @getter Table
     */
    public function getTables ()
    {
        return (array)$this->children["table"];
    }
    public function getTableByName ($name)
    {
        return $this->children["table"][$name];
    }
    /**
     * @getter Roles
     */
    public function getRoles ()
    {
        return (array)$this->children["role"];
    }
    public function getRoleByName ($name)
    {
        return $this->children["role"][$name];
    }
}
