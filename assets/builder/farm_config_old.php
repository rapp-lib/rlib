<?php
    return array(
        "farm_mark" => array("-m", "build build-".date("Ymd-His")),
        "farm_mark_find" => array("--grep=^build build-"),
        "root_mark" => array("-m", "build build-".date("Ymd-His")." init"),
        "root_find" => false,
    ) + include(constant("R_LIB_ROOT_DIR")."/assets/builder/farm_config.php");
