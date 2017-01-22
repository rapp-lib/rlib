<?php
namespace R\Lib\Core;

use ArrayObject;

class Request extends ArrayObject
{
    /**
     * 登録されている値を取得する
     */
    public function get ($key)
    {
        return array_get($this, $key);
    }
    /**
     * 値を削除する
     */
    public function delete ($key)
    {
        array_unset($this, $key);
    }
}
