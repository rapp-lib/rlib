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
<?php else: /* role has auth_table */ ?>
                "accounts" => array(
                    array("login_id"=>"<?=$role->getName()?>", "login_pw"=>"cftyuhbvg"),
                ),
<?php endif; /* role nhas auth_table */ ?>
<?php if ($role->getLoginController()): ?>
                "login_request_uri" => "id://<?=$role->getLoginController()->getName().'.login'?>",
<?php endif; /* role has login_controller */ ?>
            ),
        ),
<?php endif; /* role neq guest */ ?>
<?php endforeach; /* each roles*/ ?>
    ));
