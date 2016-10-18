<?php
namespace R\Lib\Webapp;

use ArrayObject;

class Response extends ArrayObject
{
    private static $instance = null;

    public static function getInstance ()
    {
        if ( ! isset($instance)) {
            $instance = new Response;
        }
        return $instance;
    }

    /**
     *
     */
    public function __construct ()
    {
    }
}
