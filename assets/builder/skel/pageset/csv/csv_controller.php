    /**
     * CSV設定
     */
    protected $csv_setting = array(
        "file_charset" => "SJIS-WIN",
        "data_charset" => "UTF-8",
        "rows" => array(
            "<?=$table->getIdCol()->getName()?>" => "#ID",
<?php foreach ($controller->getInputCols() as $col): ?>
            "<?=$col->getName()?>" => "<?=$col->getLabel()?>",
<?php endforeach; ?>
        ),
        "filters" => array(
            array("filter" => "sanitize"),
<?php foreach ($controller->getInputCols() as $col): ?>
<?php if ($col->getEnumSet()): ?>
            array("target" => "<?=$col->getName?>", "filter" => "list_select",
                "enum" => "<?=$col->getEnumSet()->getName()?>",
            ),
<? endif; ?>
<?php endforeach; ?>
        ),
        "ignore_empty_line" => true,
    );
<?=$pageset->getPageByType("export")->getMethodDecSource()?>
    {
        // 検索結果の取得
        $this->forms["search"]->restore();
        $res = $this->forms["search"]
            ->search()
            ->removePagenation()
            ->selectNoFetch();
        // CSVファイルの書き込み
        $csv_file = file_storage()->create("tmp");
        $csv = util("CSVHandler",array($csv_file->getFile(),"w",$this->csv_setting));
        while ($t = $res->fetch()) {
            $csv->write_line($t);
        }
        // データ出力
        response()->output(array(
            "download" => "export-".date("Ymd-his").".csv",
            "stored_file" => $csv_file,
        ));
    }
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
<?=$pageset->getPageByType("import")->getMethodDecSource()?>
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
