    /**
     * 入力フォーム
     */
    protected static $form_entry = array(
        "auto_restore" => true,
        "form_page" => ".entry_form",
<?php if ($table->hasDef()): ?>
        "table" => "<?=$table->getName()?>",
<?php endif; ?>
        "fields" => array(
<?php if ($table->hasDef()): ?>
            "id",
<?php endif; ?>
<?php foreach ($controller->getInputCols() as $col): ?>
            "<?=$col->getName()?>" => array("label"=>"<?=$col->getLabel()?>"),
<?php endforeach; ?>
        ),
        "rules" => array(
        ),
    );
<?=$pageset->getPageByType("form")->getMethodDecSource()?>
    {
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
        return redirect("page:<?=$pageset->getPageByType("complete")->getLocalPage()?>");
    }
<?=$pageset->getPageByType("complete")->getMethodDecSource()?>
    {
        if ( ! $this->forms["entry"]->isEmpty()) {
            if ( ! $this->forms["entry"]->isValid()) {
                return redirect("page:.entry_form", array("back"=>"1"));
            }
            // メールの送信
            //util("Mail")->factory()
            //    ->import("sample.php")
            //    ->assign("form", $this->forms["entry"])
            //    ->send();
            $this->forms["entry"]->getRecord()->save();
            $this->forms["entry"]->clear();
        }
        return redirect("page:<?=$controller->getIndexPage()->getLocalPage()?>", array("back"=>"1"));
    }
