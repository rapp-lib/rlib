    /**
     * 入力フォーム
     */
    protected static $form_entry = array(
        "auto_restore" => true,
        "form_page" => ".entry_form",
<?php if ( ! $t["nodef"]): ?>
        "table" => "<?=$t["name"]?>",
<?php endif; /* $t["nodef"]*/ ?>
        "fields" => array(
<?php if ( ! $t["nodef"]): ?>
            "<?=$t['pkey']?>",
<?php endif; /* $t["nodef"]*/ ?>
<?php foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
            "<?=$tc['short_name']?>"<?=$tc['field_def']?>,
<?php endforeach; ?>
        ),
        "rules" => array(
        ),
    );
    /**
     * @page
     * @title <?=$controller_label?> 入力フォーム
     */
    public function act_entry_form ()
    {
        if ($this->forms["entry"]->receive()) {
            if ($this->forms["entry"]->isValid()) {
                $this->forms["entry"]->save();
                return redirect("page:.entry_confirm");
            }
        } elseif ($id = $this->request["id"]) {
            $this->forms["entry"]->init($id);
        } elseif ( ! $this->request["back"]) {
            $this->forms["entry"]->clear();
        }
    }
    /**
     * @page
     * @title <?=$controller_label?> 確認
     */
    public function act_entry_confirm ()
    {
<?php if ($c["usage"] != "form"): ?>
        return redirect("page:.entry_exec");
<?php endif; ?>
    }
    /**
     * @page
     * @title <?=$controller_label?> 完了
     */
    public function act_entry_exec ()
    {
        if ( ! $this->forms["entry"]->isEmpty()) {
            if ( ! $this->forms["entry"]->isValid()) {
                return redirect("page:.entry_form", array("back"=>"1"));
            }
<?php if ($t["nodef"]): ?>
            // メールの送信
            util("Mail")->factory()
                ->import("sample.php")
                ->assign("form", $this->forms["entry"])
                ->send();
<?php else: /* $t["nodef"] */ ?>
            $this->forms["entry"]->getRecord()->save();
<?php endif; /* $t["nodef"] */ ?>
            $this->forms["entry"]->clear();
        }
<?php if ($c["usage"] != "form"): ?>
        return redirect("page:.view_list", array("back"=>"1"));
<?php endif; ?>
    }
