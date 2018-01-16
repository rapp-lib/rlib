<?php
namespace R\Lib\Analyzer\Def;

use R\Lib\Analyzer\NameResolver;
use R\Lib\Analyzer\PathCollection;

class SchemaDef extends Def_Base
{
    private $config;
    public function __construct()
    {
        $this->config = include(constant("R_APP_ROOT_DIR")."/.analyze.php");
    }
    public function getConfig($name)
    {
        return array_get($this->config, $name);
    }

// -- controller

    public function getController($name)
    {
        if ( ! $this->children["controllers"][$name]) {
            $this->children["controllers"][$name] = new ControllerDef($this, $name);
        }
        return $this->children["controllers"][$name];
    }
    public function getControllers()
    {
        foreach (NameResolver::getControllerNames() as $name) $this->getController($name);
        return $this->children["controllers"];
    }
    public function getForm($full_name)
    {
        list($controller_name, $name) = explode('.', $full_name, 2);
        return $this->getController($controller_name)->getForm($name);
    }
    public function getAction($full_name)
    {
        list($controller_name, $name) = explode('.', $full_name, 2);
        return $this->getController($controller_name)->getAction($name);
    }

// -- table

    public function getTable($name)
    {
        if ( ! $this->children["tables"][$name]) {
            $this->children["tables"][$name] = new TableDef($this, $name);
        }
        return $this->children["tables"][$name];
    }
    public function getTables()
    {
        foreach (NameResolver::getTableNames() as $name) $this->getTable($name);
        return $this->children["tables"];
    }
    public function getCol($full_name)
    {
        list($table_name, $name) = explode('.', $full_name, 2);
        return $this->getTable($table_name)->getCol($name);
    }

// -- enum

    public function getEnumRepo($name)
    {
        if ( ! $this->children["enum_repos"][$name]) {
            $this->children["enum_repos"][$name] = new EnumRepoDef($this, $name);
        }
        return $this->children["enum_repos"][$name];
    }
    public function getEnumRepos()
    {
        foreach (NameResolver::getEnumRepoNames() as $name) $this->getEnumRepo($name);
        return $this->children["enum_repos"];
    }

// -- webroot

    public function getWebroot($name)
    {
        if ( ! $this->children["webroots"][$name]) {
            $this->children["webroots"][$name] = new WebrootDef($this, $name);
        }
        return $this->children["webroots"][$name];
    }
    public function getWebroots()
    {
        foreach ((array)app()->http->getWebroots() as $name=>$entity) $this->getWebroot($name);
        return $this->children["webroots"];
    }
    public function getFirstRoute($name)
    {
        foreach ($this->getWebroots() as $webroot) {
            if ($route = $webroot->getRoute($name)) return $route;
        }
        return null;
    }

// -- role

    public function getRole($name)
    {
        if ( ! $this->children["roles"][$name]) {
            $this->children["roles"][$name] = new RoleDef($this, $name);
        }
        return $this->children["roles"][$name];
    }
    public function getRoles()
    {
        foreach ((array)app()->config("auth.roles") as $name=>$config) $this->getRole($name);
        return $this->children["roles"];
    }

// -- class

    public function getClass($name)
    {
        if ( ! $this->children["classes"][$name]) {
            $this->children["classes"][$name] = new ClassDef($this, $name);
        }
        return $this->children["classes"][$name];
    }
    public function getClasses()
    {
        foreach (NameResolver::getAppClasses() as $name) $this->getClass($name);
        return $this->children["classes"];
    }

// -- files

    public function getFile($name)
    {
        if ( ! $this->children["files"]) {
            $this->children["files"] = array();
            $paths = PathCollection::scanDir(constant("R_APP_ROOT_DIR"));
            foreach ((array)$this->getConfig("files.desc") as $desc) {
                $paths->addPaths($desc[0], array("desc"=>$desc[1]));
            }
            foreach ((array)$this->getConfig("files.ignore") as $path) {
                $paths->removePath($path);
            }
            foreach ($paths->getFlatten() as $file=>$attrs) {
                if ( ! file_exists($file)) continue;
                $name = NameResolver::getAppFileName($file);
                $this->children["files"][$name] = new FileDef($this, $name, (array)$attrs);
            }
        }
        return $this->children["files"][$name];
    }
    public function getFiles()
    {
        $this->getFile(false);
        return $this->children["files"];
    }
    public function getFileByFullName($filename)
    {
        $name = NameResolver::getAppFileName($filename);
        return $this->getFile($name);
    }
}
