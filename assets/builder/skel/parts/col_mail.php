<?php
    $var_name = $o["var_name"] ?: '$mail->vars["t"]';
    $name = $col->getName();
?>
<? if ($col->getAttr("type")=="assoc"): ?>
--<?="\n"?>
<#?php foreach (<?=$var_name?>["<?=$name?>"] as $key=>$item): ?>
<?php foreach ($col->getAssocTable()->getInputCols() as $assoc_col): ?>
  <?=$assoc_col->getLabel()?> : <?=$assoc_col->getMailSource(array("var_name"=>'$item'))?><?="\n"?>
<?php endforeach; /* foreach as $assoc_col */ ?>
--<?="\n"?>
<#?php endforeach; ?><?=""?>
<?php elseif ($enum_set = $col->getEnumSet()): /* if type=="assoc" */ ?>
<#?=app()->enum("<?=$enum_set->getFullName()?>")->offsetGet(<?=$var_name?>["<?=$name?>"])?><#?="\n"?><?=""?>
<?php else: /* elseif type=="enum_set" */ ?>
<#?=<?=$var_name?>["<?=$name?>"]?><#?="\n"?><?=""?>
<?php endif; /* if type=="assoc" */ ?>
