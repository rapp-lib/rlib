<?php
namespace R\Lib\Analyzer\Def;

use R\Lib\Analyzer\NameResolver;
use R\Lib\Analyzer\ReflectiveAnalyzer;

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
            if (preg_match('!^act_(.*)$!', $method->getName(), $_)) {
                $this->getAction($_[1]);
            }
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
            if (preg_match('!^form_(.*)$!', $prop->getName(), $_)) {
                $this->getForm($_[1]);
            }
        }
        return (array)$this->children["forms"];
    }
}
class ActionDef extends Def_Base
{
    public function getMethod()
    {
        return $this->getParent()->getClass()->getMethod("act_".$this->getName());
    }

// -- route

    public function getRoute()
    {
        return array_shift($routes = $this->getRouteAll());
    }
    public function getRouteAll()
    {
        $routes = array();
        foreach ($this->getSchema()->getWebroots() as $webroot) {
            $page_id = $this->getParent()->getName().".".$this->getName();
            if ($route = $webroot->getRoute($page_id)) $routes[] = $route;
        }
        return $routes;
    }
}
class FormDef extends Def_Base
{
    public function getEntity()
    {
        return app()->form[$this->getParent()->getName()][$this->getName()];
    }
    public function getDef()
    {
        if ( ! $this->def) {
            $this->def = ReflectiveAnalyzer::getPrivateValue($this->getEntity(), "def");
        }
        return $this->def;
    }

// -- field

    public function getField($name)
    {
        if ( ! $this->children["fields"]) {
            $def = $this->getDef();
            $rules = array();
            foreach ((array)$def["rules"] as $rule_def) {
                $rules[$rule_def["field_name"]][] = $rule_def;
            }
            foreach ((array)$def["fields"] as $field_name=>$field_def) {
                $this->children["fields"][$field_name] = new FieldDef($this,
                    $field_name, array("rules"=>$rules[$field_name]));
            }
        }
        return $this->children["fields"][$name];
    }
    public function getFields()
    {
        $this->getField(null);
        return (array)$this->children["fields"];
    }
}
class FieldDef extends Def_Base
{

// -- rule

    public function getRule($name)
    {
        if ( ! $this->children["rules"][$name]) {
            $this->children["rules"][$name] = new RuleDef($this,
                $name, array("rule"=>$this->attrs["rules"][$name]));
        }
        return $this->children["rules"][$name];
    }
    public function getRules()
    {
        foreach ((array)$this->attrs["rules"] as $rule_def) {
            $this->getRule($rule_def["type"]);
        }
        return (array)$this->children["rules"];
    }
}
class RuleDef extends Def_Base
{
    public function getType()
    {
        return $this->getName();
    }

// -- form

    public function getCsvFormatForm()
    {
        if ($this->getType()==="csv_file") {
            return $this->getSchema()->getForm($this->attr["rule"]["form"]);
        }
        return null;
    }
}
