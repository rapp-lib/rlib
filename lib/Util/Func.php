<?php
namespace R\Lib\Core\Util;

class Func
{
    /**
     * 再帰的にarray_mapを実行する
     */
    public static function mapRecursive ($func, $value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::mapRecursive($func, $v);
            }
        } else {
            $value = call_user_func($func, $value);
        }
        return $value;
    }
}
