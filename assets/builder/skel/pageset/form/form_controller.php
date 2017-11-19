    /**
     * 入力フォーム
     */
    protected static $form_entry = array(
        "form_page" => "<?=$pageset->getPageByType("form")->getFullPage()?>",
        "csrf_check" => true,
<?php if ($table->hasDef()): ?>
        "table" => "<?=$table->getName()?>",
<?php endif; ?>
        "fields" => array(
<?php if ($controller->getAttr("type")==="master" || $controller->isAccountMyPage()): ?>
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
        if ($this->forms["entry"]->receive($this->input)) {
            if ($this->forms["entry"]->isValid()) {
                $this->forms["entry"]->save();
<?php if ($pageset->getAttr("skip_confirm")): ?>
                return $this->redirect("id://<?=$pageset->getPageByType("complete")->getLocalPage()?>");
<?php else: ?>
                return $this->redirect("id://<?=$pageset->getPageByType("confirm")->getLocalPage()?>");
<?php endif; ?>
            }
        } elseif ($this->input["back"]) {
            $this->forms["entry"]->restore();
        } else {
            $this->forms["entry"]->clear();
<?php if ($controller->isAccountMyPage()): ?>
            $t = $this->forms["entry"]->getTable()<?=$controller->getTableChain("find")?>->selectOne();
            $this->forms["entry"]->setRecord($t);
            $this->forms["entry"]["id"] = "myself";
<?php elseif ($controller->getPagesetByType("show")): ?>
            if ($id = $this->input["id"]) {
                $t = $this->forms["entry"]->getTable()<?=$controller->getTableChain("find")?>->selectById($id);
                if ( ! $t) return $this->response("notfound");
                $this->forms["entry"]->setRecord($t);
            }
<?php endif; ?>
<?php foreach ($pageset->getParamFields() as $param_field): ?>
            if ( ! $this->forms["entry"]["<?=$param_field["field_name"]?>"]) {
                $this->forms["entry"]["<?=$param_field["field_name"]?>"] = $this->input["<?=$param_field["field_name"]?>"];
            }
<?php endforeach; ?>
        }
<?php foreach ($pageset->getParamFields() as $param_field): ?>
<?php   if ($param_field["required"]): ?>
        if ( ! $this->forms["entry"]["<?=$param_field["field_name"]?>"]) return $this->response("badrequest");
<?php   endif; ?>
<?php endforeach; ?>
    }
<?=$pageset->getPageByType("confirm")->getMethodDecSource()?>
    {
        $this->forms["entry"]->restore();
<?php if ($table->hasDef()): ?>
        $this->vars["t"] = $this->forms["entry"]->getRecord();
<?php else: ?>
        $this->vars["t"] = $this->forms["entry"]->getValues();
<?php endif; ?>
        return $this->redirect("id://<?=$pageset->getPageByType("complete")->getLocalPage()?>");
    }
<?=$pageset->getPageByType("complete")->getMethodDecSource()?>
    {
        $this->forms["entry"]->restore();
        if ( ! $this->forms["entry"]->isEmpty()) {
<?php if ($table->hasDef()): ?>
            // 登録
<?php   if ($controller->isAccountMyPage()): ?>
            $this->forms["entry"]->getTableWithValues()<?=$controller->getTableChain("find")?><?=$controller->getTableChain("save")?>->updateAll();
            $t = $this->forms["entry"]->getTable()<?=$controller->getTableChain("find")?>->selectOne();
<?php   else: ?>
            $t = $this->forms["entry"]->getTableWithValues()<?=$controller->getTableChain("save")?>->save()->getSavedRecord();
<?php   endif; ?>
<?php else: ?>
            $t = $this->forms["entry"]->getValues();
<?php endif; ?>
<?php if ($mail = $pageset->getMailByType("admin")): ?>
            // 管理者通知メールの送信
            send_mail("<?=$mail->getTemplateFile()?>", array("t"=>$t));
<?php endif; ?>
<?php if ($mail = $pageset->getMailByType("reply")): ?>
            // 自動返信メールの送信
            send_mail("<?=$mail->getTemplateFile()?>", array("t"=>$t));
<?php endif; ?>
            $this->forms["entry"]->clear();
        }
<?php if ($pageset->getAttr("skip_complete")): ?>
        return $this->redirect("id://<?=$pageset->getBackPage()->getFullPage($page)?>", array("back"=>"1"));
<?php endif; ?>
    }
