    /**
     * 登録フォーム
     */
    protected static $form_apply = array(
        "receive_all" => true,
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
<?=$pageset->getPageByType("apply")->getMethodDecSource()?>
    {
        if ($this->forms["apply"]->receive($this->input)) {
            if ($this->forms["apply"]->isValid()) {
<?php if ($table->hasDef()): ?>
                $t = $this->forms["apply"]->getTableWithValues()<?=$pageset->getTableChainSource("save")?>->getSavedRecord();
<?php else: ?>
                $t = $this->forms["apply"]->getValues();
<?php endif; ?>
                $this->flash->success(__("登録しました"));
            } else {
                report_warning("入力エラーがあります",array(
                    "form" => $this->forms["apply"]->exportState(),
                ));
                $this->flash->error(__("登録できません"));
            }
        }
        return $this->redirect("id://<?=$pageset->getBackPage()->getFullPage($page)?>", array("back"=>"1"));
    }
