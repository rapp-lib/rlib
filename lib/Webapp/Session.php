<?php
namespace R\Lib\Webapp;

use R\Lib\Core\ArrayObject;

class Session extends ArrayObject
{
    private $key;

    public static function getInstance ($key)
    {
        // 配列指定であれば連結
        if (is_array($key)) {
            $key = implode(".",$keys);
        }
        $key_parts = explode(".",$key);
        // 指定した階層で$_SESSION内から参照を取得する
        $ref = & $_SESSION;
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                $ref = array();
            }
            $ref = & $ref[$key_part];
        }
        $instance = new Session($ref, $key);
    }
    public function __construct (& $ref, $key)
    {
        $this->key = $key;
        $this->array_payload = & $ref;
    }
    public function session ($key)
    {
        if (is_array($key)) {
            $key = implode(".",$keys);
        }
        return session($this->key.".".$key);
    }
}
