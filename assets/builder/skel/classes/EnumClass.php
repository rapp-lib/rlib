<#?php
namespace R\App\Enum;

/**
 * @enum
 */
class <?=$enum->getClassName()?> extends Enum_App
{
<?php foreach ($enum->getEnumSets() as $enum_set): ?>
    /**
     * @enumset <?=$enum_set->getName()?><?="\n"?>
     */
    protected static $values_<?=$enum_set->getName()?> = array(
        "1" =>"Value-1",
        "2" =>"Value-2",
        "3" =>"Value-3",
    );
<?php endforeach; ?>
}
