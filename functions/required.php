<?php
    define("R_LIB_ROOT_DIR", realpath(__DIR__."/.."));

    require_once __DIR__."/app_functions.php";
    require_once __DIR__."/array_functions.php";
    require_once __DIR__."/report_functions.php";
    require_once __DIR__."/misc_functions.php"; // checked

    require_once __DIR__."/Regacy/array.php"; // @depreced
    require_once __DIR__."/Regacy/webapp.php"; // @depreced
    require_once __DIR__."/Regacy/vars.php"; // @depreced
    require_once __DIR__."/Regacy/lib_db.php"; // @depreced
    require_once __DIR__."/Regacy/lib_smarty.php"; // @depreced
    require_once __DIR__."/Regacy/html.php"; // @depreced
    require_once __DIR__."/Regacy/path.php"; // @depreced
    require_once __DIR__."/Regacy/string.php"; // @depreced
    require_once __DIR__."/Regacy/file.php"; // @depreced
    require_once __DIR__."/Regacy/modules.php"; // @depreced
    require_once __DIR__."/Regacy/date.php"; // @depreced
    require_once __DIR__."/Regacy/misc_deprecated.php"; // @depreced
