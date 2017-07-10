    /**
     * CSVアップロードフォーム
     */
    protected static $form_entry_csv = array(
        "auto_restore" => true,
        "fields" => array(
            "csv_file" => array("storage" => "tmp"),
        ),
        "rules" => array(
            "csv_file",
        ),
    );
<?=$pageset->getPageByType("import")->getMethodDecSource()?>
    {
        if ($this->forms["entry_csv"]->receive($this->input)) {
            if ($this->forms["entry_csv"]->isValid()) {
                $this->forms["entry_csv"]->save();
                return $this->redirect("id://<?=$pageset->getPageByType("complete")->getLocalPage()?>");
            }
        } elseif ( ! $this->input["back"]) {
            $this->forms["entry"]->clear();
        }
    }
<?=$pageset->getPageByType("complete")->getMethodDecSource()?>
    {
        if ( ! $this->forms["entry_csv"]->isEmpty()) {
            if ( ! $this->forms["entry_csv"]->isValid()) {
                return $this->redirect("id://<?=$pageset->getPageByType("import")->getLocalPage()?>", array("back"=>"1"));
            }
            // CSVファイルを開く
            $csv_file = app()->file->getFileByUri($this->forms["entry_csv"]["csv_file"])->getSource();
            $csv = new \R\Lib\Util\CSVHandler"($csv_file,"r",$this->csv_setting));
            // DBへの登録処理
            table("<?=$table->getName()?>")->transactionBegin();
            while ($t=$csv->read_line()) {
                table("<?=$table->getName()?>")->save($t);
            }
            table("<?=$table->getName()?>")->transactionCommit();
            $this->forms["entry_csv"]->clear();
        }
        return $this->redirect("id://<?=$controller->getIndexPage()->getLocalPage()?>", array("back"=>"1"));
    }
