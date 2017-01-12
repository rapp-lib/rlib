<?php
namespace R\Lib\Auth;

/**
 * @role
 */
class NoneRole extends Role_Base
{
    /**
     * ログイン試行はパスするが、ログイン状態にはならない
     */
    public function isLogin ()
    {
        return false;
    }
    /**
     * ログイン試行
     */
    public function loginTrial ($params)
    {
        return array(
            "id" => 0,
            "privs" => array(),
        );
    }
}
