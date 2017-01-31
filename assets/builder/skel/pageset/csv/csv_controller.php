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
<?=$pageset->getPageByType("download")->getMethodDecSource()?>
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
