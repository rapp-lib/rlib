<#?php
namespace R\App\Controller;

/**
 * @controller
 */
class <?=$controller->getClassName()?> extends <?=$controller->getRole()->getRoleControllerClassName()?><?="\n"?>
{
    protected static $priv_required = <?=$controller->getPrivRequired() ? "true" : "false"?>;
<?php foreach ($controller->getPagesets() as $pageset): ?>
<?=$pageset->getControllerSource()?>
<?php endforeach; ?>
}
