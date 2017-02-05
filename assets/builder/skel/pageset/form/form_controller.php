    /**
     * 入力フォーム
     */
    protected static $form_entry = array(
        "form_page" => "<?=$pageset->getPageByType("form")->getFullPage()?>",
<?php if ($table->hasDef()): ?>
        "table" => "<?=$table->getName()?>",
<?php endif; ?>
        "fields" => array(
<?php if ($table->hasDef()): ?>
            "id",
<?php endif; ?>
<?php foreach ($controller->getInputCols() as $col): ?>
<?=$col->getEntryFormFieldDefSource()?>
<?php endforeach; ?>
        ),
        "rules" => array(
        ),
    );
<?=$pageset->getPageByType("form")->getMethodDecSource()?>
    {
        $this->forms["entry"]->restore();
        if ($this->forms["entry"]->receive()) {
            if ($this->forms["entry"]->isValid()) {
                $this->forms["entry"]->save();
                return redirect("page:<?=$pageset->getPageByType("confirm")->getLocalPage()?>");
            }
        } elseif ($id = $this->request["id"]) {
            $this->forms["entry"]->init($id);
        } elseif ( ! $this->request["back"]) {
            $this->forms["entry"]->clear();
        }
    }
<?=$pageset->getPageByType("confirm")->getMethodDecSource()?>
    {
        $this->forms["entry"]->restore();
<?php if ($pageset->attr("skip_confirm")): ?>
        return redirect("page:<?=$pageset->getPageByType("complete")->getLocalPage()?>");
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
<?php if ($pageset->attr("use_mail")): ?>
            // メールの送信
            app()->mailer("<?=$controller->getName?>.php", array("t"=>$t)->send();
<?php endif; ?>
            $this->forms["entry"]->clear();
        }
<?php if ($pageset->attr("skip_complete")): ?>
        return redirect("page:<?=$pageset->getBackPage()->getFullPage($page)?>", array("back"=>"1"));
<?php endif; ?>
    }
