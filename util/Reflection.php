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
        if ($ref = self::getRef($target)) {
            return array(
                "file" => $ref->getFileName(),
                "line" => $ref->getStartLine(),
            );
        }

        return array();
    }
}