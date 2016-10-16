<?php
namespace R\Lib\Webapp;

use R\Lib\Core\ArrayObject;

class Response extends ArrayObject
{
    private static $instance = null;

    public static function getInstance ()
    {
        if ( ! isset($instance)) {
            $instance = new self;
        }
        return $instance;
    }

    public function __construct ()
    {
    }
}
