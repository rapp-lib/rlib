<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class SchemaElement extends Element_Base
{
    protected $controllers = array();
    protected $tables = array();
    protected $roles = array();
    protected $enums = array();

    protected function init ()
    {
    }
    public function loadFromSchema ($controllers, $tables)
    {
        // Controller登録
        foreach ($controllers as $controller_name => $controller_attrs) {
            $this->controllers[$controller_name] = new ControllerElement($controller_name, $controller_attrs, $this);
        }
        // Table登録
        foreach ($tables as $table_name => $table_attrs) {
            $this->tables[$table_name] = new TableElement($table_name, $table_attrs, $this);
        }
        // Role登録
        foreach ($controllers as $controller_name => $controller_attrs) {
            $role_name = $controller_attrs["access_as"];
            $role_attrs = array();
            $this->roles[$role_name] = new RoleElement($role_name, $role_attrs, $this);
        }
        // Enum登録
        $enum_set_names = array();
        foreach ($tables as $table_name => $table_attrs) {
            foreach ((array)$table_attrs["cols_all"] as $col_name => $col_attrs) {
                $enum_set_name = null;
                if ($col_attrs["enum"]) {
                    $enum_set_name = $col_attrs["enum"];
                } elseif (in_array($col_attrs["type"],array("select","radioselect","checklist"))) {
                    $enum_set_name = $table_name.".".$col_name;report_warning($enum_set_name);
                }
                if (preg_match('!^([^\.]+)\.([^\.]+)$!',$enum_set_name,$match)) {
                    list(, $enum_name, $set_name) = $match;
                    $enum_set_names[$enum_name][$set_name] = $set_name;
                }
            }
        }
        foreach ($enum_set_names as $enum_name => $set_names) {
            $enum_attrs = array(
                "enum_name" => $enum_name,
                "set_names" => $set_names,
            );
            $this->enums[$enum_name] = new EnumElement($enum_name, $enum_attrs, $this);
        }
    }
    public function getController ($controller_name=null)
    {
        return $controller_name
            ? $this->controllers[$controller_name]
            : $this->controllers;
    }
    public function getTable ($table_name=null)
    {
        return $table_name
            ? $this->tables[$table_name]
            : $this->tables;
    }
    public function getRole ($role_name=null)
    {
        return $role_name
            ? $this->roles[$role_name]
            : $this->roles;
    }
    public function getEnum ($enum_name=null)
    {
        return $enum_name
            ? $this->enums[$enum_name]
            : $this->enums;
    }
}
