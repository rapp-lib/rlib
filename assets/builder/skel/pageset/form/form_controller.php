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
<?php if ($col->getAttr("type")==="mi"): ?>
<?php if ($controller->getAttr("type")==="master"): ?>
            "<?=$col->getName()?>.*.<?=$col->getAssocTable()->getIdCol()->getName()?>",
<?php endif; ?>
<?php foreach ($col->getAssocTable()->getInputCols() as $assoc_col): ?>
<?=$assoc_col->getEntryFormFieldDefSource(array("name_parent"=>$col->getName().".*"))?>
<?php endforeach; /* foreach as $assoc_col */ ?>
<?php endif; /* if type=="mi" */ ?>
<?php endforeach /* foreach as $col */ ?>
        ),
        "rules" => array(
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
<?php if ($pageset->getAttr("use_mail")): ?>
            // メールの送信
            app()->mailer("sample.php", array("t"=>$t))->send();
<?php endif; ?>
            $this->forms["entry"]->clear();
        }
<?php if ($pageset->getAttr("skip_complete")): ?>
        return $this->redirect("id://<?=$pageset->getBackPage()->getFullPage($page)?>", array("back"=>"1"));
<?php endif; ?>
    }
