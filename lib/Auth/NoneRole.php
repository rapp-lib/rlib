<?php
namespace R\Lib\Auth;

/**
 * @role
 */
class NoneRole extends Role_Base
{
    /**
     * ログイン試行
     */
    public function loginTrial ($state)
    {
        report_error("このRoleではログイン機能は利用できません",array(
            "role" => $this,
            "method" => "loginTrial",
        ));
    }
    /**
     * ログイン時の処理
     */
    public function onLogin ()
    {
        report_error("このRoleではログイン機能は利用できません",array(
            "role" => $this,
            "method" => "onLogin",
        ));
    }
    /**
     * ログアウト時の処理
     */
    public function onLogout ()
    {
        report_error("このRoleではログイン機能は利用できません",array(
            "role" => $this,
            "method" => "onLogout",
        ));
    }
    /**
     * アクセス時の処理
     */
    public function onAccess ()
    {
        report_error("このRoleではログイン機能は利用できません",array(
            "role" => $this,
            "method" => "onAccess",
        ));
    }
    /**
     * 認証要求時の処理
     */
    public function onLoginRequired ($required)
    {
        report_error("このRoleではログイン機能は利用できません",array(
            "role" => $this,
            "method" => "onLoginRequired",
        ));
    }
}
