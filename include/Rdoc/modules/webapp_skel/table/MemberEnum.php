<?php
    $enum_class = str_camelize($enum["enum_name"])."Enum";
?><?="<!?php\n"?>
namespace R\App\Enum;

/**
 * @enum
 */
class <?=$enum_class?> extends Enum_App
{
<?php foreach ($enum["set_names"] as $set_name): ?>
    /**
     * @enumset <?=$set_name?><?="\n"?>
     */
    protected static $values_<?=$set_name?> = array(
        "1" =>"Value-1",
        "2" =>"Value-2",
        "3" =>"Value-3",
    );
<?php endforeach; ?>
}
