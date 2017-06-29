<#?php
    return array("http.webroots.routes"=>array(
<?php foreach ($schema->getControllers() as $controller): ?>

        // <?=$controller->getLabel()?><?="\n"?>
<?php foreach ($controller->getPagesets() as $pageset): ?>
<?php foreach ($pageset->getPages() as $page): ?>
        array("<?=$page->getFullPage()?>", "<?=$page->getPath()?>"),
<?php endforeach; ?>
<?php endforeach; ?>
<?php endforeach; ?>
    ));
