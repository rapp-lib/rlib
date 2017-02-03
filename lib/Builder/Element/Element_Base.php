<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class Element_Base
{
    protected $name;
    protected $attrs = array();
    protected $parent;
    protected $children = array();
    public function __construct ($name="", $attrs=array(), $parent=null)
    {
        $this->name = $name;
        array_add($this->attrs, (array)$attrs);
        $this->parent = $parent;
        $this->init();
    }
    protected function init ()
    {
        // Overrideして処理を記述
    }
    public function deploy ($recursive=false)
    {
        $deploy_callbacks = (array)$this->getSchema()->getConfig("deploy.".$this->getElementType());
        foreach ($deploy_callbacks as $deploy_callback) {
            call_user_func($deploy_callback, $this);
        }
        // 再帰的に関係要素もdeploy実行
        if ($recursive) {
            foreach ($this->children as $type => $elements) {
                foreach ($elements as $element) {
                    $element->deploy($recursive);
                }
            }
        }
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
        if ( ! $this->parent) {
            return $this;
        } else {
            return $this->parent->getSchema();
        }
    }
    /**
     * 要素のTypeを小文字で返す
     */
    public function getElementType ()
    {
        $element_type = null;
        $class = get_class($this);
        if (preg_match('!(\w+)Element$!', $class, $match)) {
            $element_type = str_underscore($match[1]);
        }
        return $element_type;
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
