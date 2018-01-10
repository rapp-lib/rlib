<?php
namespace R\Lib\Analyzer;

use ReflectionObject;

/**
 * Reflection機能による解析機能
 */
class ReflectiveAnalyzer
{
    /**
     * 対象のObjectのprivate属性値を取得する
     */
    public static function getPrivateValue($class, $prop_name)
    {
        $ref_class = new ReflectionObject($class);
        $ref_prop = $ref_class->getProperty($prop_name);
        $last_accessible = $ref_prop->isPublic();
        $ref_prop->setAccessible(true);
        $value = $ref_prop->getValue($class);
        $ref_prop->setAccessible($last_accessible);
        return $value;
    }
}
