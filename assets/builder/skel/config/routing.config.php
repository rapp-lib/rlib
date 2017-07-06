<#?php
    return array("http.webroots.www.routes"=>array(
<?php foreach ($schema->getRoles() as $role): ?>
        // <?=$role->getName()?><?="\n"?>
        array(array(
<?php foreach ($schema->getControllers() as $controller): ?>
<?php if($role->getName()==$controller->getRole()->getName()): ?>
            // <?=$controller->getLabel()?><?="\n"?>
<?php foreach ($controller->getPagesets() as $pageset): ?>
<?php foreach ($pageset->getPages() as $page): ?>
            <?=$page->getRouteSource()?><?="\n"?>
<?php endforeach; /* each pagesets */ ?>
<?php endforeach; /* each pages */ ?>
<?php endif; /* controller_role eq role  */ ?>
<?php endforeach; /* each controllers */ ?>
<?php if($role->getName()=="guest"): ?>
        ), "", array("auth.role"=>"guest")),
<?php else: /* role eq guest  */ ?>
        ), "", array("auth.role"=>"<?=$role->getName()?>", "auth.priv_req"=>true)),
<?php endif; /* role neq guest  */ ?>
<?php endforeach; /* each roles */ ?>
    ));
