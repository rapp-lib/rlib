    /**
     * CSV設定
     */
    protected static $form_csv = array(
        "table" => "<?=$table->getName()?>",
        "fields" => array(
            "<?=$table->getIdCol()->getName()?>"=>array("label"=>"<?=$table->getIdCol()->getAttr("label")?>"),
<?php foreach ($controller->getInputCols() as $col): ?>
<?=$col->getEntryFormFieldDefSource(array("pageset"=>$pageset))?>
<?php endforeach; ?>
        ),
        "rules" => array(
<?php foreach ($controller->getInputCols() as $col): ?>
<?=$col->getRuleDefSource(array("pageset"=>$pageset))?>
<?php endforeach ?>
        ),
        "csv_setting" => array(
            "ignore_empty_line" => true,
            "filters" => array(
<?php foreach ($controller->getInputCols() as $col): ?>
<?php   if ($col->getAttr("type")==="assoc"): ?>
<?php       foreach ($col->getAssocTable()->getInputCols() as $assoc_col): ?>
<?php           if ($assoc_col->getEnumSet()): ?>
                array("<?=$col->getName()?>.0.<?=$assoc_col->getName()?>", "enum_value", "enum"=>"<?=$assoc_col->getEnumSet()->getFullName()?>"),
<?php           endif; ?>
<?php       endforeach; /* foreach as $assoc_col */ ?>
<?php   else: /* if type=="assoc" */ ?>
<?php       if ($col->getEnumSet()): ?>
                array("<?=$col->getName()?>", "enum_value", "enum"=>"<?=$col->getEnumSet()->getFullName()?>"),
<?php       endif; ?>
<?php   endif; /* if type=="assoc" */ ?>
<?php endforeach; ?>
            ),
        ),
    );
<?=$pageset->getPageByType("download")->getMethodDecSource()?>
    {
        // 検索結果の取得
        $this->forms["search"]->restore();
        $ts = $this->forms["search"]->search()<?=$pageset->getTableChainSource("find")?>->removePagenation()->select();
        // CSVファイルの書き込み
        $csv = $this->forms["csv"]->openCsvFile("php://temp", "w");
        foreach ($ts as $t) $csv->writeRecord($t);
        // データ出力
        return app()->http->response("stream", $csv->getHandle(), array("headers"=>array(
            'content-type' => 'application/octet-stream',
            'content-disposition' => 'attachment; filename='.'<?=$table->getName()?>.csv'
        )));
    }
