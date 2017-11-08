<?php
namespace R\Lib\Enum;

use ArrayAccess;

class EnumRepositry implements ArrayAccess
{
    private $value_repos = array();

    public function offsetExists($key)
    {
        if ( ! isset($this->value_repos[$key])) {
            $parts = explode(".", $key, 2);
            $class_name = 'R\App\Enum\\'.$parts[0].'Enum';
            $class_name = app()->i18n->getLocalizedClass($class_name);
            if ( ! class_exists($class_name)) {
                report_error("Enum参照先Classが定義されていません", array("class_name"=>$class_name));
            }
            $this->value_repos[$key] = new $class_name($parts[1]);
        }
        return isset($this->value_repos[$key]);
    }
    public function offsetGet($key)
    {
        return $this->offsetExists($key) ? $this->value_repos[$key] : null;
    }
    public function offsetSet($key, $value)
    {
    }
    public function offsetUnset($key)
    {
    }
}
