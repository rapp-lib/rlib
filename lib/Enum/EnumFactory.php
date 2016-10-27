<?php
namespace R\Lib\Enum;

/**
 * Enumインスタンスの生成/取得を行う
 */
class EnumFactory
{
    private static $instance = null;

    private $enums = array();

    /**
     * EnumFactoryインスタンスを取得する
     */
    public static function getInstance ($enum_set_name=null, $group=null)
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new EnumFactory();
        }
        return isset($enum_set_name)
            ? self::$instance->getEnum($enum_set_name, $group)
            : self::$instance;
    }

    /**
     * Enumインスタンスを取得する
     */
    public function getEnum ($enum_set_name, $parent_key=null)
    {
        // Enumインスタンスを作成する
        $enum_id = $enum_set_name.":".$parent_key;
        if ( ! isset($this->enums[$enum_id])) {
            if (preg_match('!^([^\.]+)(?:\.([^\.]+))?$!', $enum_set_name, $match)) {
                list(, $enum_name, $set_name) = $match;
                $enum_class = "R\\App\\Enum\\".str_camelize($enum_name)."Enum";
                if (class_exists($enum_class)) {
                    $this->enums[$enum_id] = new $enum_class($set_name);
                }
            }
        }
        return $this->enums[$enum_id];
    }
}
