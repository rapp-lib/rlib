    /**
     * 入力フォーム
     */
    protected static $form_entry = array(
        "form_page" => "<?=$pageset->getPageByType("form")->getFullPage()?>",
<?php if ($table->hasDef()): ?>
        "table" => "<?=$table->getName()?>",
<?php endif; ?>
        "fields" => array(
<?php if ($controller->getAttr("type")==="master"): ?>
            "id",
<?php endif; ?>
<?php foreach ($controller->getInputCols() as $col): ?>
<?=$col->getEntryFormFieldDefSource()?>
<?php   if ($col->getAttr("type")==="assoc"): ?>
<?php       if ($controller->getAttr("type")==="master" && ! $col->getAttr("def.assoc.single")): ?>
            "<?=$col->getName()?>.*.<?=$col->getAssocTable()->getIdCol()->getName()?>",
<?php       endif; ?>
<?php       if (($assoc_ord_col = $col->getAssocTable()->getOrdCol()) && ! $assoc_ord_col->getAttr("type")): ?>
            "<?=$col->getName()?>.*.<?=$assoc_ord_col->getName()?>",
<?php       else: /* if $assoc_ord_col */ ?>
            "<?=$col->getName()?>.*.ord_seq"=>array("col"=>false),
<?php       endif; /* if $assoc_ord_col */ ?>
<?php       foreach ($col->getAssocTable()->getInputCols() as $assoc_col): ?>
<?=$assoc_col->getEntryFormFieldDefSource(array("name_parent"=>$col->getName().".*"))?>
<?php       endforeach; /* foreach as $assoc_col */ ?>
<?php   endif; /* if type=="assoc" */ ?>
<?php endforeach; /* foreach as $col */ ?>
        ),
        "rules" => array(
<?php foreach ($controller->getInputCols() as $col): ?>
<?php   if ($col->getAttr("type")==="assoc"): ?>
<?php       foreach ($col->getAssocTable()->getInputCols() as $assoc_col): ?>
<?=$assoc_col->getRuleDefSource(array("name_parent"=>$col->getName().".*"))?>
<?php       endforeach; /* foreach as $assoc_col */ ?>
<?php   else: /* if type=="assoc" */ ?>
<?=$col->getRuleDefSource()?>
<?php   endif; /* if type=="assoc" */ ?>
<?php endforeach /* foreach as $col */ ?>
        ),
    );
<?=$pageset->getPageByType("form")->getMethodDecSource()?>
    {
        $this->forms["entry"]->restore();
        if ($this->forms["entry"]->receive($this->input)) {
            if ($this->forms["entry"]->isValid()) {
                $this->forms["entry"]->save();
                return $this->redirect("id://<?=$pageset->getPageByType("confirm")->getLocalPage()?>");
            }
        } elseif ($id = $this->input["id"]) {
            $this->forms["entry"]->init($id);
        } elseif ( ! $this->input["back"]) {
            $this->forms["entry"]->clear();
        }
    }
<?=$pageset->getPageByType("confirm")->getMethodDecSource()?>
    {
        $this->forms["entry"]->restore();
<?php if ($pageset->getAttr("skip_confirm")): ?>
        return $this->redirect("id://<?=$pageset->getPageByType("complete")->getLocalPage()?>");
<?php elseif ($table->hasDef()): ?>
        $this->vars["t"] = $this->forms["entry"]->getRecord();
<?php else: ?>
        $this->vars["t"] = $this->forms["entry"]->getValues();
<?php endif; ?>
    }
<?=$pageset->getPageByType("complete")->getMethodDecSource()?>
    {
        $this->forms["entry"]->restore();
        if ( ! $this->forms["entry"]->isEmpty()) {
<?php if ($table->hasDef()): ?>
            // 登録
            $t = $this->forms["entry"]->getRecord();
            $t->save();
<?php else: ?>
            $t = $this->forms["entry"]->getValues();
<?php endif; ?>
<?php foreach ($pageset->getMails() as $mail): ?>
            // メールの送信
            send_mail("<?=$mail->getTemplateFile()?>", array("t"=>$t));
<?php endforeach; ?>
            $this->forms["entry"]->clear();
        }
<?php if ($pageset->getAttr("skip_complete")): ?>
        return $this->redirect("id://<?=$pageset->getBackPage()->getFullPage($page)?>", array("back"=>"1"));
<?php endif; ?>
    }
