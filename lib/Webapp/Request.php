<?php
namespace R\Lib\Webapp;

use ArrayObject;

class Request extends ArrayObject
{
    private static $instance = null;

    public static function getInstance ()
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new Request;
        }
        return self::$instance;
    }

    /**
     *
     */
    public function __construct ()
    {
        /*
        // FILESの値を設定
        foreach ((array)$_FILES as $part_0 => $values_0) {
            if (isset($values_0["tmp_name"])) {
                $this[$part_0] = "UPLOADED";
            } else {
                foreach ((array)$values_0 as $part_1 => $values_1) {
                    if (isset($values_1["tmp_name"])) {
                        $this[$part_0][$part_1] = "UPLOADED";
                    } else {
                        foreach ((array)$values_1 as $part_2 => $values_2) {
                            if (isset($values_2["tmp_name"])) {
                                $this[$part_0][$part_1][$part_2] = "UPLOADED";
                            }
                        }
                    }
                }
            }
        }
        */
        // GET/POSTの値を設定
        foreach ((array)$_REQUEST as $k => $v) {
            $this[$k] = $v;
        }
    }

    /**
     * Responseにアクセスする
     */
    public function response ()
    {
        return Response::getInstance();
    }

    /**
     * Sessionにアクセスする
     */
    public function session ($key)
    {
        return Session::getInstance($key);
    }
}
