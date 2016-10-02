<?php
    print "<!?php\n";
?>

    registry(array(
        "Routing.page_to_path" =>array(
<?php foreach (builder()->getController() as $controller): ?>

            // <?=$controller->getAttr('label')?><?="\n"?>
<?php foreach ($controller->getAction() as $action): ?>
            "<?=$action->getPage()?>" => "<?=$action->getPath()?>",
<?php endforeach; ?>
<?php endforeach; ?>
        ),
    ));
