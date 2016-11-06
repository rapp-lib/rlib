<#?php
    webroot("www")->addRouting(array(
        "include.static" => "/include/*",
        "service.file" => "/file:/*",
<?php foreach ($schema->getController() as $controller): ?>

        // <?=$controller->getAttr('label')?><?="\n"?>
<?php foreach ($controller->getAction() as $action): ?>
        "<?=$action->getPage()?>" => "<?=$action->getPath()?>",
<?php endforeach; ?>
<?php endforeach; ?>
    ));
