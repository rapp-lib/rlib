<?php
namespace R\Lib\Webapp;

use R\Lib\Core\ArrayObject;

class Request extends ArrayObject
{
    public function __construct ()
    {
        $this->array_payload = $_REQUEST;
    }
}
