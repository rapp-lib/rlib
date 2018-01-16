<?php
namespace R\Lib\Analyzer\Def;

use \ReflectionClass;

class ClassDef extends Def_Base
{
    protected $ref = null;
    public function getRef()
    {
        if ( ! $this->ref) $this->ref = new ReflectionClass($this->getName());
        return $this->ref;
    }
    public function getFile()
    {
        $filename = $this->getRef()->getFileName();
        return $this->getSchema()->getFileByFullName($filename);
    }

// -- prop

    public function getProp($name)
    {
        if ( ! $this->children["props"][$name]) {
            $this->children["props"][$name] = new PropDef($this, $name);
        }
        return $this->children["props"][$name];
    }
    public function getProps()
    {
        foreach ($this->getRef()->getProperties() as $_prop) {
            $this->getProp($_prop->getName());
        }
        return $this->children["props"];
    }

// -- method

    public function getMethod($name)
    {
        if ( ! $this->children["methods"][$name]) {
            $this->children["methods"][$name] = new MethodDef($this, $name);
        }
        return $this->children["methods"][$name];
    }
    public function getMethods()
    {
        foreach ($this->getRef()->getMethods() as $_method) {
            $this->getMethod($_method->getName());
        }
        return $this->children["methods"];
    }
}
class MethodDef extends Def_Base
{
}
class PropDef extends Def_Base
{
}
