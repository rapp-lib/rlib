<?php
namespace R\Lib\Analyzer\Def;

use R\Lib\Analyzer\NameResolver;

class ControllerDef extends Def_Base
{
    public function getClass()
    {
        $class_name = NameResolver::getControllerClassByName($this->getName());
        return $this->getSchema()->getClass($class_name);
    }

// -- action

    public function getAction($name)
    {
        if ( ! $this->children["actions"][$name]) {
            $this->children["actions"][$name] = new ActionDef($this, $name);
        }
        return $this->children["actions"][$name];
    }
    public function getActions()
    {
        foreach ($this->getClass()->getMethods() as $method) {
            if (preg_match('!^act_(.*)$!', $method->getName(), $_)) $this->getAction($_[1]);
        }
        return $this->children["actions"];
    }

// -- form

    public function getForm($name)
    {
        if ( ! $this->children["forms"][$name]) {
            $this->children["forms"][$name] = new FormDef($this, $name);
        }
        return $this->children["forms"][$name];
    }
    public function getForms()
    {
        foreach ($this->getClass()->getProps() as $prop) {
            if (preg_match('!^form_(.*)$!', $prop->getName(), $_)) $this->getForm($_[1]);
        }
        return $this->children["forms"];
    }
}
class ActionDef extends Def_Base
{
    public function getRoutes()
    {}
}
class FormDef extends Def_Base
{
    public function getFields()
    {}
}
class FieldDef extends Def_Base
{
    public function getRules()
    {}
    public function getCsvFormatForm()
    {}
}
class RuleDef extends Def_Base
{
}
