<?php
namespace R\Lib\Webapp;

use ArrayObject;

class Response extends ArrayObject
{
    private static $instance = null;

    public static function getInstance ()
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new Response;
        }
        return self::$instance;
    }

    /**
     *
     */
    public function __construct ()
    {
    }
}
