    /**
     * カートフォーム
     */
    protected static $form_cart = array(
<?php if ($table->hasDef()): ?>
        "table" => "<?=$table->getName()?>",
<?php endif; ?>
        "fields" => array(
<?php foreach ($controller->getInputCols() as $col): ?>
<?php   if ($col->getAttr("type")=="assoc"): ?>
<?=$col->getEntryFormFieldDefSource(array("pageset"=>$pageset))?>
<?php   endif; ?>
<?php endforeach; ?>
        ),
        "rules" => array(
<?php foreach ($controller->getInputCols() as $col): ?>
<?php   if ($col->getAttr("type")=="assoc"): ?>
<?=$col->getRuleDefSource(array("pageset"=>$pageset))?>
<?php   endif; ?>
<?php endforeach ?>
        ),
    );
<?=$pageset->getPageByType("cart")->getMethodDecSource()?>
    {
        $this->forms["cart"]->restore();
<?php foreach ($controller->getInputCols() as $col): ?>
<?php   if ($col->getAttr("type")=="assoc"): ?>
<?php       if ($param_field = $pageset->getParamFieldByName($col->getName())): ?>
        if ($id = $this->input["<?=$param_field["field_name"]?>"]["<?=$param_field["assoc_field_name"]?>"]) {
            $amount = $this->input["amount"] ?: +1;
            $detail = & $this->forms["cart"]["<?=$param_field["field_name"]?>"][$id];
            $detail["<?=$param_field["assoc_field_name"]?>"] = $id;
<?php           if ($amount_col = $col->getAssocTable()->getColByAttr("def.amount")): ?>
            $detail["<?=$amount_col->getName()?>"] += $amount;
            if ($detail["<?=$amount_col->getName()?>"] < 1) unset($this->forms["cart"]["<?=$param_field["field_name"]?>"][$id]);
<?php           else: ?>
            if ($amount < 0) unset($this->forms["cart"]["<?=$param_field["field_name"]?>"][$id]);
<?php           endif; ?>
            if ($this->forms["cart"]->isValid()) {
                $this->forms["cart"]->save();
            } else {
                $this->flash->error(___("反映できませんでした"));
            }
            return $this->redirect("id://<?=$pageset->getPageByType("cart")->getLocalPage()?>");
        }
<?php       endif; ?>
<?php   endif; ?>
<?php endforeach; ?>
        if ($this->input["fix"]) {
            $this->forms["cart"]->saveTo("fix");
<?php if (($links = $pageset->getLinkTo()) && ($link = $links[0])): ?>
            <?=$link["pageset"]->getLinkSource("redirect", $pageset)?><?="\n"?>
<?php else: ?>
            return $this->redirect("id://<?=$pageset->getPageByType("cart")->getLocalPage()?>");
<?php endif; ?>
        }
        $this->vars["t"] = $this->forms["cart"]->getRecord();
    }
