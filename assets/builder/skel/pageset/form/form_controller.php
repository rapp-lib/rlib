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
<?php if ($pageset->getFlg("is_master")): ?>
            "<?=$table->getIdCol()->getName()?>"=>array("label"=>"<?=$table->getIdCol()->getAttr("label")?>"),
<?php endif; ?>
<?php foreach ($controller->getInputCols() as $col): ?>
<?=$col->getEntryFormFieldDefSource(array("pageset"=>$pageset))?>
<?php endforeach; ?>
        ),
        "rules" => array(
<?php foreach ($controller->getInputCols() as $col): ?>
<?=$col->getRuleDefSource(array("pageset"=>$pageset))?>
<?php endforeach ?>
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
<?php if ($pageset->getFlg("is_edit")): ?>
            $t = $this->forms["entry"]->getTable()<?=$pageset->getTableChainSource("find")?>->selectOne();
            $this->forms["entry"]->setRecord($t);
            if ( ! $t) return $this->response("badrequest");
<?php elseif ($pageset->getFlg("is_master")): ?>
            if ($id = $this->input["id"]) {
                $t = $this->forms["entry"]->getTable()<?=$pageset->getTableChainSource("find")?>->selectById($id);
                if ( ! $t) return $this->response("notfound");
                $this->forms["entry"]->setRecord($t);
            }
<?php endif; ?>
<?php foreach ($pageset->getParamFields("depend") as $param_field): ?>
            if ( ! $this->forms["entry"]["<?=$param_field["field_name"]?>"]) {
                $this->forms["entry"]["<?=$param_field["field_name"]?>"] = $this->input["<?=$param_field["field_name"]?>"];
            }
<?php endforeach; ?>
        }
<?php foreach ($pageset->getParamFields("depend") as $param_field): ?>
        if ( ! $this->forms["entry"]["<?=$param_field["field_name"]?>"]) return $this->response("badrequest");
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
            $t = $this->forms["entry"]->getTableWithValues()<?=$pageset->getTableChainSource("save")?>->getSavedRecord();
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
<?php if ($pageset->getAttr("skip_complete")===true): ?>
        return $this->redirect("id://<?=$pageset->getBackPage()->getLocalPage()?>", array("back"=>"1"));
<?php elseif (($skip_complete = $pageset->getAttr("skip_complete")) && is_string($skip_complete)): ?>
        return $this->redirect("id://<?=$skip_complete?>");
<?php endif; ?>
    }
