<#?php
    return array("router.webroot.www.routing"=>array(
<?php foreach ($schema->getControllers() as $controller): ?>

        // <?=$controller->getLabel()?><?="\n"?>
<?php foreach ($controller->getPagesets() as $pageset): ?>
<?php foreach ($pageset->getPages() as $page): ?>
        "<?=$page->getFullPage()?>" => "<?=$page->getPath()?>",
<?php endforeach; ?>
<?php endforeach; ?>
<?php endforeach; ?>
    ));
