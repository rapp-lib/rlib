<#?php
namespace R\App\Controller;

/**
 * @controller
 */
class <?=$controller->getClassName()?> extends <?=$controller->getRole()->getRoleControllerClassName()?><?="\n"?>
{
<?php foreach ($controller->getPagesets() as $pageset): ?>
<?=$pageset->getControllerSource()?>
<?php endforeach; ?>
}
