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
                //"accounts" => array(array("login_id"=>"<?=$role->getName()?>", "login_pw"=>"cftyuhbvg", "priv"=>array("id"=>9999999))),
<?php else: /* role has auth_table */ ?>
                "accounts" => array(array("login_id"=>"<?=$role->getName()?>", "login_pw"=>"cftyuhbvg", "priv"=>array("id"=>9999999))),
<?php endif; /* role nhas auth_table */ ?>
<?php if ($role->getLoginController()): ?>
                "login_request_uri" => "id://<?=$role->getLoginController()->getName().'.login'?>",
<?php endif; /* role has login_controller */ ?>
                "check_priv" => function($priv_req, $priv){
                    if ($priv_req && ! $priv) return false;
                    return true;
                }
            ),
        ),
<?php endif; /* role neq guest */ ?>
<?php endforeach; /* each roles*/ ?>
    ));
