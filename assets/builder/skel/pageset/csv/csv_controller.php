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
            array("target" => "<?=$col->getName()?>", "filter" => "list_select",
                "enum" => "<?=$col->getEnumSet()->getFullName()?>",
            ),
<?php endif; ?>
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
        $csv_file = app()->file_storage->create("tmp",null,array(
            "original_filename" => "<?=$table->getName()?>-".date("Ymd-His").".csv",
        ));
        $csv = util("CSVHandler",array($csv_file->getFile(),"w",$this->csv_setting));
        while ($t = $res->fetch()) {
            $csv->write_line($t);
        }
        // データ出力
        return app()->response->downloadStoredFile($csv_file);
    }
