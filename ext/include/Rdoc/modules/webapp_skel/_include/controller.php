<?php

    $controller = $c["name"];
    $controller_class = str_camelize($controller)."Controller";
    $controller_label = $c["label"];

    $role_required = $c["auth"];
    $role_login = $c["account"];
    $role_accessor = $t["accessor"];

    $table = $t["name"];

    $__controller_header = "";
    ob_start();
?><?="<!?php\n\n"?>
/**
 * @controller
 */
class <?=$controller_class?> extends Controller_App
{
    /**
     * 認証設定
     */
<?php if ($role_required): ?>
    protected $access_as = <?=$role_required?>;
    protected $priv_required = true;
<?php else: ?>
    protected $access_as = null;
    protected $priv_required = false;
<?php endif; ?>

<?php
    $__controller_header = ob_get_clean();

    $__model_instance = 'model("'.$table.'")';

    $__table_instance = 'table("'.$table.'")';
