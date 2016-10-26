<?php
namespace R\Lib\Enum;

/*
    table:Xxx.yyy => XxxTable::$enum_yyy
    table:Xxx.yyy => XxxTable::enum_yyy()
    table:Xxx.yyy, zzz => XxxTable::$enum_grouped_yyy["zzz"]
    table:Xxx.yyy, zzz => XxxTable::enum_grouped_yyy("zzz")
    enum:Xxx.yyy => XxxEnum::$enum_yyy
    enum:Xxx.yyy => XxxEnum::enum_yyy()
    enum:Xxx.yyy, zzz => XxxEnum::$enum_grouped_yyy["zzz"]
    enum:Xxx.yyy, zzz => XxxEnum::enum_grouped_yyy("zzz")
    list:xxx => XxxTable->options()

    $city_id|enum:"Product.city":$pref
    $city_id|enum:"Product.city":$pref
*/

class EnumFactory implements EnumRepositry
{
    private static $instance = null;

    private $repositry_callback = array(
        "enum" => "R\\Lib\\Enum\\EnumFactory::findEnum",
        "table" => "R\\Lib\\Enum\\EnumFactory::findEnumInTable",
    );

    public static function getInstance ($enum_name=null, $group=null)
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new EnumFactory();
        }
        return isset($enum_name)
            ? self::$instance->getEnum($enum_name, $group)
            : self::$instance;
    }

    public function getEnum ($enum_name, $group=null)
    {
        if (preg_match('!^(?:([^\:]+):)([^\.]+)$!', $enum_name, $match)) {
            list(, $repositry_name, $enum_name) = $match;
            if ($repositry_name) {
                $callback = $this->repositry_callback[$repositry_name];
                return call_user_func($callback, $enum_name, $group);
            } else {
                foreach ($this->repositry_callback[$repositry_name] as $callback) {
                    $enum = call_user_func($callback, $enum_name, $group);
                    if (isset($enum)) {
                        return $enum;
                    }
                }
            }
        }
    }

    /**
     *
     */
    public static function findEnum ($enum_name, $group=null)
    {
        if (preg_match('!^([^\.]+)\.([^\.]+)$!', $enum_name, $match)) {
            $class_name = "R\\App\\Enum\\".str_camelize($match[1])."Enum";
            $member_name = isset($group)
                ? "enum_grouped_".$match[2]
                : "enum_".$match[2];
            if (method_exists($class,$member)) {
                return isset($group)
                    ? call_user_func(array($class,$member), $group)
                    : call_user_func(array($class,$member));
            } elseif (property_exists($class,$member)) {
                if (is_array($class::$member)) {
                    return isset($group)
                        ? $class::$member[$group]
                        : $class::$member;
                } elseif (is_string($class::$member)) {
                    return enum()->getEnum($class::$member, $group);
                }
            }
        }
    }

    /**
     *
     */
    public static function findEnumInTable ($enum_name, $group=null)
    {
        if (preg_match('!^([^\.]+)\.([^\.]+)$!', $enum_name, $match)) {
            $class_name = "R\\App\\Table\\".str_camelize($match[1])."Table";
            $member_name = isset($group)
                ? "enum_grouped_".$match[2]
                : "enum_".$match[2];
            if (method_exists($class,$member)) {
                return isset($group)
                    ? call_user_func(array($class,$member), $group)
                    : call_user_func(array($class,$member));
            } elseif (property_exists($class,$member)) {
                if (is_array($class::$member)) {
                    return isset($group)
                        ? $class::$member[$group]
                        : $class::$member;
                } elseif (is_string($class::$member)) {
                    return enum()->getEnum($class::$member, $group);
                }
            }
        }
    }
}
