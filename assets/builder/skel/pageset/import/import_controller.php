    /**
     * CSVアップロードフォーム
     */
    protected static $form_entry_csv = array(
        "auto_restore" => true,
        "fields" => array(
            "csv_file" => array("file_upload_to" => "tmp"),
        ),
        "rules" => array(
            "csv_file",
        ),
    );
<?=$pageset->getPageByType("form")->getMethodDecSource()?>
    {
        if ($this->forms["entry_csv"]->receive()) {
            if ($this->forms["entry_csv"]->isValid()) {
                $this->forms["entry_csv"]->save();
                return redirect("page:<?=$pageset->getPageByType("complete")->getLocalName()?>");
            }
        } elseif ( ! $this->request["back"]) {
            $this->forms["entry"]->clear();
        }
    }
<?=$pageset->getPageByType("complete")->getMethodDecSource()?>
    {
        if ( ! $this->forms["entry_csv"]->isEmpty()) {
            if ( ! $this->forms["entry_csv"]->isValid()) {
                return redirect("page:.entry_csv_form", array("back"=>"1"));
            }
            // CSVファイルを開く
            $csv_file = file_storage()->get($this->forms["entry_csv"]["csv_file"]);
            $csv = util("CSVHandler", array($csv_file->getFile(),"r",$this->csv_setting));
            // DBへの登録処理
            table("<?=$table->getName()?>")->transactionBegin();
            while ($t=$csv->read_line()) {
                table("<?=$table->getName()?>")->save($t);
            }
            table("<?=$table->getName()?>")->transactionCommit();
            $this->forms["entry_csv"]->clear();
        }
        return redirect("page:<?=$controller->getIndexPage()->getLocalPage()?>", array("back"=>"1"));
    }
