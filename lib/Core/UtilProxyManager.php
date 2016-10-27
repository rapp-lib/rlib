<?php
namespace R\Lib\Core;

/**
 *
 */
class UtilProxyManager
{
    private static $singleton_proxy =array();
    private static $static_proxy =array();

    /**
     * クラスに対応するMethodCallProxyの取得
     */
    public static function getProxy ($class_name, $singleton=false) {
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
        if ( ! $singleton) {
            if ( ! self::$static_proxy[$class_name]) {
                self::$static_proxy[$class_name] = new MethodCallProxy($class_name);
            }
            return self::$static_proxy[$class_name];
        // Singletonオブジェクトのメソッド呼び出し
        } else {
            if ( ! self::$singleton_proxy[$class_name]) {
                self::$singleton_proxy[$class_name] = new MethodCallProxy(new $class_name());
            }
            return self::$singleton_proxy[$class_name];
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