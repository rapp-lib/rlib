<?php
namespace R\Lib\Analyzer\Def;

class Def_Base
{
    protected $name;
    protected $attrs;
    protected $parent;
    protected $children = array();
    public function __construct ($name="", $attrs=array(), $parent=null)
    {
        $this->name = $name;
        $this->attrs = $attrs;
        $this->parent = $parent;
        $this->init();
    }
    protected function init ()
    {
        // Overrideして処理を記述
    }
    public function getName ()
    {
        return $this->name;
    }
    public function getAttr ($key)
    {
        return array_get($this->attrs, $key);
    }
    public function getParent ()
    {
        return $this->parent;
    }
    public function getSchema ()
    {
        if ( ! $this->parent) return $this;
        return $this->parent->getSchema();
    }
    /**
     * 要素のTypeを小文字で返す
     */
    public function getDefType ()
    {
        if (preg_match('!(\w+)Def$!', get_class($this), $match)) return str_underscore($match[1]);
        return null;
    }
    public function __report ()
    {
        return array(
            "name" => $this->name,
            "attrs" => $this->attrs,
            "children" => $this->children,
        );
    }
}
class NameResolver
{
    public function getControllerNameByClass($class)
    {
    }
    public function getControllerClassByName($name)
    {
    }
    public function getControllerNames()
    {
        $glob = constant("R_APP_ROOT_DIR")."/app/Controller/*Controller.php";
        foreach (glob($glob) as $file) if (preg_match('!/(\w+)Controller\.php$!', $file, $_)) {
            $name = str_underscore($_[1]);
        }
    }
}
class SchemaDef extends Def_Base
{
    public function getController($name)
    {
        if ( ! $this->children["controllers"][$name]) {
            $this->children["controllers"][$name] = new ControllerDef($this, $name);
        }
        return $this->children["controllers"][$name];
    }
    public function getControllers()
    {
        $result = array();
        $files = $this->getFilesByType("controller_class");
        foreach ($files as $file) {
            if (preg_match('!/(\w+)Controller\.php$!', $file->getFileName(), $match)) {
                $name = str_underscore($match[1]);
                $result[$name] = $this->getController($name);
            }
        }
        return $result;
    }
    public function getTables()
    {}
    public function getEnumRepos()
    {}
    public function getPages()
    {}
    public function getForms()
    {}
    public function getCsvFormats()
    {}
    public function getRoles()
    {}
    public function getClasses()
    {}
    public function getFiles()
    {}
    public function getFilesByType($type)
    {
        $app_root_dir = $this->getSchema()->getConfig("app_root_dir");
        $globs = $this->getSchema()->getConfig("file_type_globs.".$type);
        foreach (is_array($globs) ? $globs : array($globs) as $glob) {
            $glob = $app_root_dir."/".$glob;
            foreach (glob($glob) as $file_name) {
                if (is_file($file_name)) {
                    $name = preg_replace('!^'.preg_quote($app_root_dir,'!').'/!', '', $file_name);
                    $this->getFile($name);
                }
            }
        }
    }
    public function getConfig($key)
    {
        $config = array(
            "app_root_dir" => constant("R_APP_ROOT_DIR"),
            "file_type_globs" => array(
                "controller_class" => "app/Controller/*.php",
                "table_class" => "app/Table/*.php",
                "enum_class" => "app/Enum/*.php",
                "config_file" => "config/*.php",
                "mail_template_file" => "mail/*.php",
            ),
        );
    }
}
class FileDef extends Def_Base
{
    public function getFileName()
    {}
    public function getFileType()
    {}
}
class ClassDef extends Def_Base
{
    public function getFile()
    {}
    public function getClassName()
    {}
    public function getComment()
    {}
}
class ClassMemberDef extends Def_Base
{
    public function getClass()
    {}
    public function getComment()
    {}
}
class ControllerDef extends ClassDef
{
    public function getPages()
    {}
    public function getForms()
    {}
    public function getCsvFormats()
    {}
}
class PageDef extends ClassMemberDef
{
    public function getPath()
    {}
    public function getPageId()
    {}
    public function getPageFile()
    {}
}
class FormDef extends ClassMemberDef
{
    public function getFields()
    {}
}
class FieldDef extends SchemaDef
{
    public function getRules()
    {}
}
class RuleDef extends SchemaDef
{
}
class CsvFormatDef extends ClassMemberDef
{
    public function getCsvCols()
    {}
}
class CsvColDef extends SchemaDef
{
}
class TableDef extends ClassDef
{
    public function getCols()
    {}
}
class ColDef extends SchemaDef
{
}
class EnumRepoDef extends ClassDef
{
    public function getEnums()
    {}
}
class EnumDef extends ClassMemberDef
{
}
class RoleDef extends SchemaDef
{
}
