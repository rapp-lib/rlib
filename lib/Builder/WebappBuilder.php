<?php
namespace R\Lib\Builder {

use R\Lib\Builder\Element\SchemaElement;

/**
 *
 */
class WebappBuilder
{
    static $schema = null;

    public static function getSchema ()
    {
        if (static::$schema) {
            return static::$schema;
        }
        return static::$schema = new SchemaElement;
    }
}

}
namespace R\Lib\Builder\Element {

/**
 *
 */
abstract class Element_Base
{
    protected $name;
    protected $attrs;
    protected $parent;

    abstract protected function init ();

    public function __construct ($name="", $attrs=array(), $parent=null)
    {
        $this->name = $name;
        $this->attrs = $attrs;
        $this->parent = $parent;

        $this->init();
    }

    public function getName ()
    {
        return $this->name;
    }

    public function getAttr ($key=null)
    {
        if ( ! $key) {
            return $this->attrs;
        }
        return $this->attrs[$key];
    }

    public function getParent ()
    {
        return $this->parent;
    }

    public function getRoot ()
    {
        if ( ! $this->parent) {
            return $this;
        } else {
            return $this->getParent()->getRoot();
        }
    }
}

/**
 *
 */
class SchemaElement extends Element_Base
{
    protected $schema = null;
    protected $controllers = array();

    protected function init ()
    {
    }

    public function loadFromSchema ($controllers, $tables)
    {
        // Controller登録
        foreach ($controllers as $controller_name => $controller_attrs) {
            $this->controllers[$controller_name] = new ControllerElement(
                $controller_name, $controller_attrs, $this);
        }

        // Table登録
        foreach ($tables as $table_name => $table_attrs) {
            $this->tables[$table_name] = new TableElement(
                $table_name, $table_attrs, $this);
        }
    }

    public function getController ($controller_name=null)
    {
        if ( ! $controller_name) {
            return $this->controllers;
        }
        return $this->controllers[$controller_name];
    }
}

/**
 *
 */
class ControllerElement extends Element_Base
{
    protected $actions = array();

    protected function init ()
    {
        $list =array();

        if ($this->getAttr("type") == "index") {
            $list["index"] =array("label"=>"INDEX", "has_html"=>true);
        }
        if ($this->getAttr("type") == "login") {
            $list["index"] =array("label"=>"TOP", "has_html"=>false);
            $list["login"] =array("label"=>"ログイン", "has_html"=>true);
            $list["logout"] =array("label"=>"ログアウト", "has_html"=>false);
        }
        if ($this->getAttr("type") == "master") {
            $list["index"] =array("label"=>"TOP", "has_html"=>false);
            if ($this->getAttr("usage") != "form") {
                $list["view_list"] =array("label"=>"一覧", "has_html"=>true);
            }
            if ($this->getAttr("usage") != "view") {
                $list["entry_form"] =array("label"=>"入力", "has_html"=>true);
                $list["entry_confirm"] =array("label"=>"入力確認", "has_html"=>true);
                $list["entry_exec"] =array("label"=>"入力完了", "has_html"=>true);
            }
            if ($this->getAttr("usage") != "view" && $this->getAttr("use_csv")) {
                $list["entry_csv_form"] =array("label"=>"CSVインポート", "has_html"=>true);
                $list["entry_csv_confirm"] =array("label"=>"CSVインポート 確認", "has_html"=>false);
                $list["entry_csv_exec"] =array("label"=>"CSVインポート 完了", "has_html"=>false);
            }
            if ($this->getAttr("usage") != "form" && $this->getAttr("use_csv")) {
                $list["view_csv"] =array("label"=>"CSVエクスポート", "has_html"=>false);
            }
        }

        foreach ($list as $action_name => $action_attrs) {
            $this->actions[$action_name] = new ActionElement(
                $action_name, $action_attrs, $this);
        }
    }

    public function getAction ($action_name=null)
    {
        if ( ! $action_name) {
            return $this->actions;
        }
        return $this->actions[$action_name];
    }
}

/**
 *
 */
class ActionElement extends Element_Base
{
    protected function init ()
    {
    }

    public function getPath ()
    {
        $controller_name = $this->getParent()->getName();
        $action_name = $this->getName();

        $path = "/".str_replace('_','/',$controller_name);

        // act_index単一であれば階層を上げる
        if (count($this->getParent()->getAction())==1 && $action_name=="index") {
            $path .= ".html";
        } else {
            $path .= "/".$action_name.".html";
        }

        return $path;
    }

    public function getPage ()
    {
        $controller_name = $this->getParent()->getName();
        $action_name = $this->getName();
        return $controller_name.".".$action_name;
    }
}

/**
 *
 */
class TableElement extends Element_Base
{
    protected $cols = array();

    protected function init ()
    {
        $list = $this->attrs["cols"];
        foreach ($list as $col_name => $col_attrs) {
            $this->cols[$col_name] = new ColElement(
                $col_name, $col_attrs, $this);
        }
    }

    public function getCol ($col_name=null)
    {
        if ( ! $col_name) {
            return $this->cols;
        }
        return $this->actions[$col_name];
    }
}

/**
 *
 */
class ColElement extends Element_Base
{
    protected function init ()
    {
    }
}


}
