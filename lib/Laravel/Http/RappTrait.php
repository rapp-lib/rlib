<?php
namespace R\Lib\Laravel\Http;

use Illuminate\Http\Request;
use R\Lib\Form\FormContainer;

trait RappTrait
{
    public function table($table_name)
    {
        if ( ! $this->table_defs[$table_name]) {
            $def_attr_set = app("table.def_resolver")->getTableDefAttrSet($table_name);
            $this->table_defs[$table_name] = app()->make("table.def", array("def_attr_set"=>$def_attr_set));
        }
        return $this->table_defs[$table_name];
    }
    public function forms($name)
    {
        $class_name = get_class($this);
        if (preg_match('!^(.+)::(.+)$!', $name, $_)) {
            $class_name = $_[1];
            $name = $_[2];
        }
        $var_name = "form_".$name;
        $form_id = $class_name."::".$var_name;
        if ( ! $this->forms[$form_id]) {
            $def = $class_name::$$var_name;
            $def["form_name"] = $name;
            $def["tmp_storage_name"] = $form_id;
            $this->forms[$form_id] = new FormContainer($def);
        }
        return $this->forms[$form_id];
    }
}
