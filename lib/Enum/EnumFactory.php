<?php
namespace R\Lib\Enum;

/**
 * Enumインスタンスの生成/取得を行う
 */
class EnumFactory
{
    public function invoke ($enum_set_name=false, $group=false)
    {
        return $this->getEnum($enum_set_name, $group);
    }
    private $enums = array();
    /**
     * Enumインスタンスから値を取得する
     */
    public function selectValue ($value, $enum_set_name=false, $group=false)
    {
        $this->getEnum($enum_set_name, $group)->offsetGet($value);
    }

    /**
     * Enumインスタンスを取得する
     */
    public function getEnum ($enum_set_name, $parent_key=false)
    {
        // Enumインスタンスを作成する
        $enum_id = $enum_set_name.":".$parent_key;
        if ( ! isset($this->enums[$enum_id])) {
            if (preg_match('!^([^\.]+)(?:\.([^\.]+))?$!', $enum_set_name, $match)) {
                list(, $enum_name, $set_name) = $match;
                $enum_class = "R\\App\\Enum\\".str_camelize($enum_name)."Enum";
                if (class_exists($enum_class)) {
                    $this->enums[$enum_id] = new $enum_class($set_name, $parent_key);
                } else {
                    report_error("Enumクラスが定義されていません", array(
                        "enum_class" => $enum_class,
                        "enum_set_name" => $enum_set_name,
                    ));
                }
            } else {
                report_error("enum指定が不正です", array(
                    "enum_set_name" => $enum_set_name,
                ));
            }
        }
        return $this->enums[$enum_id];
    }
}
