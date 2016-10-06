<?php
namespace R\Util;
use ReflectionMethod;
use ReflectionFunction;;
use ReflectionObject;
use ReflectionClass;

/**
 *
 */
class Reflection
{
    /**
     * Reflectorオブジェクトを取得
     */
    public static function getRef ($target) {
        if (is_callable($target)) {
            if (is_array($target) && method_exists($target[0], $target[1])) {
                return new ReflectionMethod($target[0], $target[1]);
            }
            return new ReflectionFunction($target);
        }

        if (is_object($target)) {
            return new ReflectionObject($target);
        }

        if (class_exists($target)) {
            return new ReflectionClass($target);
        }

        return null;
    }

    /**
     * 定義箇所を取得
     */
    public static function getDefinedAt ($target) {
        $ref = self::getRef($target);

        if ( ! $ref) {
            report_warning("Reflectionが取得できません",array("target"=>$target));
            return array();
        }

        return array(
            "file" => $ref->getFileName(),
            "line" => $ref->getStartLine(),
        );
    }

    /**
     * 定義されているメソッド一覧を取得
     */
    public static function getMethodNames ($class_name) {
        if (is_object($class_name)) {
            $class_name = get_class($class_name);
        }

        $ref = self::getRef($class_name);

        if ( ! $ref) {
            report_warning("Reflectionが取得できません",array("target"=>$target));
            return array();
        }

        $method_names =array();

        foreach ($ref->getMethods() as $ref_method) {
            $method_names[] = $ref_method->getName();
        }

        return $method_names;
    }
}