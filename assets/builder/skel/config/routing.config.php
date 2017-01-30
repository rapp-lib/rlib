<#?php
    return array("router.webroot.www.routing"=>array(
<?php foreach ($schema->getController() as $controller): ?>

        // <?=$controller->getLabel()?><?="\n"?>
<?php foreach ($controller->getPagesets() as $pageset): ?>
<?php foreach ($pageset as $page_name => $page): ?>
        "<?=$page_name?>" => "<?=$page->getPath()?>",
<?php endforeach; ?>
<?php endforeach; ?>
<?php endforeach; ?>
    ));
