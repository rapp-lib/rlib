<#?php
    return array("router.webroot.www.routing"=>array(
<?php foreach ($schema->getController() as $controller): ?>

        // <?=$controller->getAttr('label')?><?="\n"?>
<?php foreach ($controller->getAction() as $action): ?>
        "<?=$action->getPage()?>" => "<?=$action->getPath()?>",
<?php endforeach; ?>
<?php endforeach; ?>
    ));
