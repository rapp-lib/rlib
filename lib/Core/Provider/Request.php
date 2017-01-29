<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\Provider;
use ArrayObject;

class Request extends ArrayObject implements Provider
{
    public function __construct ()
    {
        parent::__construct();
        if (php_sapi_name()!=="cli") {
            $request_values = util("Func")->mapRecursive(function($value) {
                return htmlspecialchars($value, ENT_QUOTES);
            }, array_merge($_GET, $_POST));
            $this->exchangeArray($request_values);
        }
    }
}
