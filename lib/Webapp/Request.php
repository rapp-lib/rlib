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
    /**
     * @override
     */
    public function __get ($offset)
    {
        if ($offset=="response") {
            return $this->response();
        } elseif ($offset=="session") {
            return $this->session();
        }
    }
}
