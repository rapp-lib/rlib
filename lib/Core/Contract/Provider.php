<?php
namespace R\Lib\Core\Contract;

interface Provider
{
    /**
     * Providerのインスタンス生成方法の定義
     */
    // public static function factory ();
    /**
     * Providerの生成後に呼び出す
     */
    // public function boot ();
    /**
     * Providerを関数として呼び出した際のCallback
     */
    // public function invoke ();
}
