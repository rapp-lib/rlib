<?php
namespace R\Lib\Core\Contract;

interface Container
{
    /**
     * 生成後に呼び出す
     */
    public function init ($init_params);
}
