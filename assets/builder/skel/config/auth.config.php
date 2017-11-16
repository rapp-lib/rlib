<#?php
    return array("auth.roles"=>array(
<?php foreach ($schema->getRoles() as $role): ?>
<?php if ($role->getName()!="guest"): ?>
        "<?=$role->getName()?>" => array(
            "login.class" => 'R\Lib\Auth\ConfigBasedLogin',
            "login.options" => array(
                "persist" => "session",
<?php if ($role->getAuthTable()): ?>
                "auth_table" => "<?=$role->getAuthTable()->getName()?>",
<?php endif;?>
<?php if ($role->getLoginController()): ?>
                "login_request_uri" => "id://<?=$role->getLoginController()->getName().'.login'?>",
<?php endif; /* role has login_controller */ ?>
                "authenticate" => function($params){
                    if ($params["type"]=="idpw") {
<?php if ($role->getAuthTable()): ?>
                        // if ("<?=$role->getName()?>"==$params["login_id"] && "cftyuhbvg"==$params["login_pw"]) {
                        //    return array("id"=>9999999);
                        // }
                        return table("<?=$role->getAuthTable()->getName()?>")
                            ->findByLoginIdPw($params["login_id"], $params["login_pw"])
                            ->selectOne();
<?php else:?>
                        if ("<?=$role->getName()?>"==$params["login_id"] && "cftyuhbvg"==$params["login_pw"]) {
                            return array("id"=>9999999);
                        }
<?php endif;?>
                    }
                },
                "check_priv" => function($priv_req, $priv){
                    if ($priv_req && ! $priv) return false;
                    return true;
                },
                "refresh_priv" => function($priv){
                    if ($priv) {
                        // 強制ログアウト
                        if ($priv["ts_logout"] && $priv["ts_logout"] < time() - 2*60*60) return null;
                        $priv["ts_logout"] = time();
<?php if ($role->getAuthTable()): ?>
                        // 権限情報の更新
                        if ( ! $priv["ts_refresh"]) $priv["ts_refresh"] = time();
                        if ($priv["ts_refresh"] < time() - 60*60) {
                            return table("Staff")->selectById($priv["id"]);
                        }
<?php endif;?>
                    }
                    return $priv;
                },
            ),
        ),
<?php endif; /* role neq guest */ ?>
<?php endforeach; /* each roles*/ ?>
    ));
