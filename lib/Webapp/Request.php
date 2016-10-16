<?php
namespace R\Lib\Webapp;

use R\Lib\Core\ArrayObject;

class Request extends ArrayObject
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
        $this->array_payload = $_REQUEST;
    }
}
