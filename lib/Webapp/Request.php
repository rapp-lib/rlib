<?php
namespace R\Lib\Webapp;

use ArrayObject;

class Request extends ArrayObject
{
    private static $instance = null;

    public static function getInstance ()
    {
        if ( ! isset($instance)) {
            $instance = new Request;
        }
        return $instance;
    }

    /**
     *
     */
    public function __construct ()
    {
        foreach ($_REQUEST as $k => $v) {
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
        return new Session($key);
    }
}
