<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class Element_Base
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
    protected function deply ($recursive=false)
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
        return $this->attrs[$key];
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
            return $this->getParent()->getSchema();
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
}
