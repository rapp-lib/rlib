<?php

    $controller = $c["name"];
    $controller_class = str_camelize($controller)."Controller";
    $controller_label = $c["label"];

    $role_required = $c["auth"];
    $role_login = $c["accessor"];
    $role_accessor = $c["accessor"];

    $table = $t["name"];

    $__table_instance = 'table("'.$table.'")';
    ob_start();

?><?="<!?php\n\n"?>
namespace R\App\Controller;

/**
 * @controller
 */
class <?=$controller_class?> extends Controller_App
{
    /**
     * 認証設定
     */
    protected static $access_as = "<?=$role_accessor?>";
    protected static $priv_required = <?=$role_required ? "true" : "false"?>;

<?php
    $__controller_header = ob_get_clean();
