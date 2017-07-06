<#?php
    return array("auth.roles"=>array(
<?php foreach ($schema->getRoles() as $role): ?>
        "<?=$role->getName()?>" => array(
            "login.class" => 'R\Lib\Auth\ConfigBasedLogin',
            "login.options" => array(
                "persist" => "session",
<?php if ($role->getAuthTable()): ?>
                "auth_table" => "<?=$role->getAuthTable()->getName()?>",
<?php else: ?>
                "accounts" => array(
                    array("login_id"=>"<?=$role->getName()?>", "login_pw"=>"cftyuhbvg"),
                ),
<?php endif; ?>
                "login_request_uri" => "id://<?=$role->getLoginController()->getName()?>.login",
            ),
        ),
<?php endforeach; ?>
    ));
