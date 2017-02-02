<?php
    require_once __DIR__."/../../../bootstrap.php";
    try {
        app_init('R\Lib\Core\Container\ConfigBasedApplication', array(
            "config" => include(constant("R_APP_ROOT_DIR")."/config/config.php"),
            "tags" => array("console","console-rexe"),
        ));
        app()->console->execCurrentCommand();
        exit(0);
    } catch (R\Lib\Core\Exception\ResponseException $e) {
        $response = $e->getResponse();
        $response->render();
        exit(1);
    }
