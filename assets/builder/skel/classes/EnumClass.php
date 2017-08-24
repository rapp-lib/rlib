<#?php
namespace R\App\Enum;

/**
 * @enum
 */
class <?=$enum->getClassName()?> extends Enum_App
{
<?php foreach ($enum->getEnumSets() as $enum_set): ?>
<?php   if ($fkey_for_table_name = $enum_set->getCol()->getAttr("def.fkey_for")): ?>
<?php       $fkey_for_table = $enum_set->getSchema()->getTableByName($fkey_for_table_name); ?>
    protected static function values_<?=$enum_set->getName()?> ($keys)
    {
        $query = table("<?=$fkey_for_table->getName()?>");
        if ($keys) $query->findById($keys);
        return $query->select()->getHashedBy("<?=$fkey_for_table->getIdCol()->getName()?>", "<?=$fkey_for_table->getLabelCol()->getName()?>");
    }
<?php   else: /* if $fkey_for_table_name */ ?>
    protected static $values_<?=$enum_set->getName()?> = array(
<?php       if ($values = $enum_set->getCol()->getAttr("enum_values")): ?>
<?php           foreach ($values as $k=>$v): ?>
        "<?=$k?>" => "<?=$v?>",
<?php           endforeach; ?>
<?php       else: /* if $values */ ?>
        "1" =>"Value-1",
        "2" =>"Value-2",
        "3" =>"Value-3",
<?php       endif; /* if $values */ ?>
    );
<?php   endif; /* if $fkey_for_table_name */ ?>
<?php endforeach; ?>
}
