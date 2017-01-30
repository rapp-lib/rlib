<#?php
namespace R\App\Enum;

/**
 * @enum
 */
class <?=$enum->getClassName()?> extends Enum_App
{
<?php foreach ($enum->getSetNames() as $set_name): ?>
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
