<?php
    $var_name = $o["var_name"] ?: '$mail->vars["t"]';
    $name = $col->getName();
?>
<?php if ($col->getAttr("type")=="assoc"): ?>
--<?="\n"?>
<#?php foreach (<?=$var_name?>["<?=$name?>"] as $key=>$item): ?>
<?php   foreach ($col->getAssocTable()->getAssocInputCols($col) as $assoc_col): ?>
<?php       if ($assoc_col->getAttr("type")==="assoc") continue; ?>
  <?=$assoc_col->getLabel()?> : <?=$assoc_col->getMailSource(array("var_name"=>'$item'))?><?="\n"?>
<?php   endforeach; /* foreach as $assoc_col */ ?>
--<?="\n"?>
<#?php endforeach; ?><?=""?>
<?php elseif ($enum_set = $col->getEnumSet()): ?>
<#?=app()->enum["<?=$enum_set->getFullName()?>"][<?=$var_name?>["<?=$name?>"]]?><#?="\n"?><?=""?>
<?php elseif ($col->getAttr("type")=="file"): ?>
<#?=app()->http->getServedRequest()->getUri()->getWebroot()->uri(<?=$var_name?>["<?=$name?>"])?><#?="\n"?><?=""?>
<?php elseif ($col->getAttr("type")=="date"): ?>
str_date(<?=$var_name?>["<?=$name?>"], "Y/m/d");<#?="\n"?><?=""?>
<?php elseif ($col->getAttr("type")=="datetime"): ?>
str_date(<?=$var_name?>["<?=$name?>"], "Y/m/d H:i");<#?="\n"?><?=""?>
<?php else: ?>
<#?=<?=$var_name?>["<?=$name?>"]?><#?="\n"?><?=""?>
<?php endif; ?>
