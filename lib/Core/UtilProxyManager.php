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
        if (class_exists($class_name)) {
        } elseif (class_exists($found_class_name = "R\\Lib\\Util\\".$class_name)) {
            $class_name = $found_class_name;
        } elseif (class_exists($found_class_name = "R\\App\\Util\\".$class_name)) {
            $class_name = $found_class_name;
        } else {
            report_error("クラスが定義されていません",array(
                "class_name" => $class_name,
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
        return call_user_func_array(array($this->class_name,$method_name), $args);
    }
}