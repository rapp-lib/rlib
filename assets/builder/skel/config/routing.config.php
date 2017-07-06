<#?php
    return array("http.webroots.www.routes"=>array(
<?php foreach ($schema->getRoles() as $role): ?>
<?php foreach ($schema->getControllers() as $controller): ?>
<?php if($role->getName()==$controller->getRole()->getName()): ?>
        // <?=$role->getName()=="guest" ? "ログイン不要" : $role->getLabel()."ログイン" ?><?="\n"?>
        array(array(
            // <?=$controller->getLabel()?><?="\n"?>
<?php foreach ($controller->getPagesets() as $pageset): ?>
<?php foreach ($pageset->getPages() as $page): ?>
            <?=$controller->getRouteSource()?><?="\n"?>
<?php endforeach; /* each pagesets */ ?>
<?php endforeach; /* each pages */ ?>
        ), "", array("auth.role"=>"<?=$role->getName()?>")),
<?php endif; /* controller_role eq role  */ ?>
<?php endforeach; /* each controllers */ ?>
<?php endforeach; /* each roles */ ?>
    ));
