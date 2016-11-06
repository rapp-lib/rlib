<?php
namespace R\Lib\Core;

/**
 *
 */
class UtilProxyManager
{
    private static $proxies =array();

    /**
     * クラスに対応するMethodCallProxyの取得
     */
    public static function getProxy ($class_name, $constructor_args=false) {
        // Classの探索
        if (class_exists($found_class_name_app = "R\\App\\Util\\".$class_name)) {
            $class_name = $found_class_name_app;
        } elseif (class_exists($found_class_name_lib = "R\\Lib\\Util\\".$class_name)) {
            $class_name = $found_class_name_lib;
        } elseif (class_exists($found_class_name_core = "R\\Lib\\Core\\Util\\".$class_name)) {
            $class_name = $found_class_name_core;
        } else {
            report_error("クラスが定義されていません",array(
                "class_name" => $class_name,
                "find_class_name" => array(
                    $found_class_name_app,
                    $found_class_name_lib,
                    $found_class_name_core,
                ),
            ));
        }
        // Staticメソッドの呼び出し
        if ($constructor_args===false) {
            if ( ! self::$proxies[$class_name]) {
                self::$proxies[$class_name] = new MethodCallProxy($class_name);
            }
            return self::$proxies[$class_name];
        // インスタンスの作成
        } else {
            $ref = new \ReflectionClass($class_name);
            return $ref->newInstanceArgs($constructor_args);
        }
    }
}

/**
 *
 */
class MethodCallProxy
{
    private $class;
    /**
     * @override
     */
    public function __construct ($class)
    {
        $this->class = $class;
    }
    /**
     * @override
     */
    public function __call ($method_name, $args)
    {
        if ( ! is_callable(array($this->class,$method_name))) {
            report_error("メソッドが呼び出せません",array(
                "is_static" => is_string($this->class),
                "class" => $this->class,
                "method_name" => $method_name,
            ));
        }
        return call_user_func_array(array($this->class,$method_name), $args);
    }
}