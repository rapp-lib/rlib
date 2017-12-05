<?php
namespace R\Lib\Form;

use ArrayAccess;

class FormCollection implements ArrayAccess
{
    protected $def;
    protected $forms = array();
    public function __construct ($def)
    {
        $this->def = $def;
    }
    public function offsetExists($key)
    {
        if ( ! isset($this->forms[$key])) $this->initForm($key);
        return isset($this->forms[$key]);
    }
    public function offsetGet($key)
    {
        return $this->offsetExists($key) ? $this->forms[$key] : null;
    }
    public function offsetSet($key, $value)
    {
    }
    public function offsetUnset($key)
    {
        if ($this->forms[$key]) $this->forms[$key]->clear();
        unset($this->forms[$key]);
    }
    private function initForm($key)
    {
        $def = $this->def;
        $def["form_name"] = $def["form_name"]."__".$key;
        $def["tmp_storage_name"] = $def["tmp_storage_name"]."__".$key;
        $this->forms[$key] = new FormContainer($def);
    }
}