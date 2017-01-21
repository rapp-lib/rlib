<?php
namespace R\Lib\Util;

/**
 * $_SERVERの値に関する処理
 */
class ServerVars
{
    /**
     * IPアドレスが指定した範囲にマッチするかどうかを判定する
     * @param  [type] $accept    '203.0.113.0/24'のようなCIDR
     * @param  [type] $remote_ip 判定対象のIP、指定がなければ$_SERVER['REMOTE_ADDR']
     * @return bool              判定結果
     */
    public static function ipCheck ($accept, $remote_ip=null)
    {
        if (is_null($remote_ip)) {
            $remote_ip = $_SERVER['REMOTE_ADDR'];
        }
        list($accept_ip, $mask) = explode('/', $accept);
        if (strlen($mask)===0) {
            $mask = 32;
        }
        $accept_long = ip2long($accept_ip) & ~((1 << (32 - $mask)) - 1);
        $remote_long = ip2long($remote_ip) & ~((1 << (32 - $mask)) - 1);
        return $accept_long == $remote_long;
    }
}