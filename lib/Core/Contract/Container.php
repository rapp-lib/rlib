<?php
namespace R\Lib\Core\Contract;

interface Container
{
    /**
     * 生成後に呼び出す
     */
    public function init ($init_params);
    /**
     * 実行して応答を返す
     */
    public function exec ();
}
