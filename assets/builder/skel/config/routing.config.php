<#?php
    return array("http.webroots.www.routes"=>array(
<?php foreach ($schema->getControllers() as $controller): ?>

        // <?=$controller->getLabel()?><?="\n"?>
<?php foreach ($controller->getPagesets() as $pageset): ?>
<?php foreach ($pageset->getPages() as $page): ?>
        array("<?=$page->getFullPage()?>", "<?=$page->getPathPattern()?>"<?php if($page->getName()=="static"): ?>, array("static_route"=>true)<?php endif; ?>),
<?php endforeach; ?>
<?php endforeach; ?>
<?php endforeach; ?>
    ));
