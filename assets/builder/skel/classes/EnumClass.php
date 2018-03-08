<#?php
namespace R\App\Enum;

/**
 * @enum
 */
class <?=$enum->getClassName()?> extends Enum_App
{
<?php foreach ($enum->getEnumSets() as $enum_set): ?>
<?php   if ($fkey_for_table = $enum_set->getCol()->getFkeyForTable()): ?>
    protected static function values_<?=$enum_set->getName()?> ($keys)
    {
        $query = table("<?=$fkey_for_table->getName()?>");
        if ($keys) $query->findById($keys);
        return $query->select()->getHashedBy("<?=$fkey_for_table->getIdCol()->getName()?>", "<?=$fkey_for_table->getLabelCol()->getName()?>");
    }
<?php       foreach ($fkey_for_table->getOwnedByCols() as $owned_by_col): ?>
<?php           $owned_role = $owned_by_col->getFkeyForTable()->getAuthRole(); ?>
    protected static function values_<?=$enum_set->getName()?>_for_<?=$owned_role->getName()?> ($keys)
    {
        return table("<?=$fkey_for_table->getName()?>")->findBy("<?=$owned_by_col->getName()?>", $keys)->selectHashedBy("<?=$owned_by_col->getName()?>", "<?=$fkey_for_table->getIdCol()->getName()?>", "<?=$fkey_for_table->getLabelCol()->getName()?>");
    }
<?php       endforeach; ?>
<?php   else: /* if $fkey_for_table */ ?>
    protected static $values_<?=$enum_set->getName()?> = array(
<?php       if ($values = $enum_set->getCol()->getAttr("enum_values")): ?>
<?php           foreach ($values as $k=>$v): ?>
        "<?=$k?>" => "<?=$v?>",
<?php           endforeach; ?>
<?php       else: /* if $values */ ?>
        "1" => "Value-1",
        "2" => "Value-2",
        "3" => "Value-3",
<?php       endif; /* if $values */ ?>
    );
<?php   endif; /* if $fkey_for_table */ ?>
<?php endforeach; ?>
}
