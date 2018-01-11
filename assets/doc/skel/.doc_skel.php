<?php
    return array(
        "webroots" => array(
            "www" => array("dir"=>constant("R_APP_ROOT_DIR")."/html"),
        ),
        "files.desc" => array(
            array("/app", ""),
            array("/app/Controller", ""),
            array("/app/Table", ""),
            array("/app/Enum", ""),
            array("/app/Command", ""),
            array("/app/View", ""),
            array("/config", ""),
            array("/mail", ""),
            array("/locale", ""),
            array("/bin", ""),
            array("/vendor", ""),
            array("/.git", ""),
            array("/tmp", ""),
            array("/html", ""),
        ),
        "files.ignore" => array(
            "/vendor/*",
            "/.git/*",
            "/tmp/*",
            "/html/*",
        ),
        "shelf_dir" => constant("R_APP_ROOT_DIR")."/../doc",
    );
