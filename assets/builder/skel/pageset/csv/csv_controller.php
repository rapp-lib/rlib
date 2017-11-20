    /**
     * CSV設定
     */
    protected static $csv_setting = array(
        "ignore_empty_line" => true,
        "rows" => array(
            "<?=$table->getIdCol()->getName()?>" => "#ID",
<?php foreach ($controller->getInputCols() as $col): ?>
<?php   if ($col->getAttr("type")==="assoc"): ?>
<?php       if ($controller->getAttr("type")==="master" && ! $col->getAttr("def.assoc.single")): ?>
            "<?=$col->getName()?>.0.<?=$col->getAssocTable()->getIdCol()->getName()?>" => "<?=$col->getLabel()?>[0] #ID",
<?php       endif; ?>
<?php       foreach ($col->getAssocTable()->getInputCols() as $assoc_col): ?>
            "<?=$col->getName()?>.0.<?=$assoc_col->getName()?>" => "<?=$col->getLabel()?>[0] <?=$assoc_col->getLabel()?>",
<?php       endforeach; /* foreach as $assoc_col */ ?>
<?php   else: /* if type=="assoc" */ ?>
            "<?=$col->getName()?>" => "<?=$col->getLabel()?>",
<?php   endif; /* if type=="assoc" */ ?>
<?php endforeach; /* foreach as $col */ ?>
        ),
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
    );
<?=$pageset->getPageByType("download")->getMethodDecSource()?>
    {
        // 検索結果の取得
        $this->forms["search"]->restore();
        $ts = $this->forms["search"]->search()<?=$pageset->getTableChainSource("find")?>->("find")?>->removePagenation()->select();
        // CSVファイルの書き込み
        $csv = csv_open("php://temp", "w", self::$csv_setting);
        $csv->writeLines($ts);
        // データ出力
        return app()->http->response("stream", $csv->getHandle(), array("headers"=>array(
            'content-type' => 'application/octet-stream',
            'content-disposition' => 'attachment; filename='.'<?=$table->getName()?>.csv'
        )));
    }
