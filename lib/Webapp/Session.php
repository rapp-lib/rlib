<?php
namespace R\Lib\Webapp;

use R\Lib\Core\ArrayAccessObject;

/**
 * $_SESSIONへのアクセス
 */
class Session extends ArrayAccessObject
{
    /**
     * $_SESSIONのルートを起点にSessionインスタンスを作成する
     */
    public static function getInstance ($key=null)
    {
        return new Session($key);
    }

    /**
     *
     */
    public function __construct ($key)
    {
        parent::__construct($_SESSION, $key);
    }
}