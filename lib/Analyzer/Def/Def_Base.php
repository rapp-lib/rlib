<?php
namespace R\Lib\Analyzer\Def;

class Def_Base
{
    protected $parent;
    protected $name;
    protected $attrs;
    protected $children = array();
    public function __construct ($parent, $name, $attrs=array())
    {
        if ( ! strlen($name)) {
            report_error("nome属性が空白です", array(
                "class" => get_class($this),
            ));
        }
        $this->parent = $parent;
        $this->name = $name;
        $this->attrs = $attrs;
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
        return $this->getDefType()==="schema" ? $this : $this->parent->getSchema();
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
        );
    }
}
