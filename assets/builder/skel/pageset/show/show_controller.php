    /**
     * 検索フォーム
     */
    protected static $form_search = array(
        "receive_all" => true,
        "search_page" => "<?=$pageset->getPageByType("list")->getFullPage()?>",
        "search_table" => "<?=$table->getName()?>",
        "fields" => array(
            "freeword" => array("search"=>"word", "target_col"=>array(<?php foreach ($controller->getListCols() as $col): ?><?php if ($col->getAttr("def.type")=="text"): ?>"<?=$col->getName()?>",<?php endif; ?><?php endforeach; ?>)),
            "p" => array("search"=>"page", "volume"=>20),
            "order" => array("search"=>"sort", "default"=>"<?=$table->getIdCol()->getName()?>@ASC"),
        ),
    );
<?=$pageset->getPageByType("list")->getMethodDecSource()?>
    {
        if ($this->request["back"]) {
            $this->forms["search"]->restore();
        } elseif ($this->forms["search"]->receive()) {
            $this->forms["search"]->save();
        }
        $this->vars["ts"] = $this->forms["search"]->search()->select();
    }
<?=$pageset->getPageByType("detail")->getMethodDecSource()?>
    {
        $this->vars["t"] = table("<?=$table->getName()?>")->selectById($this->request["id"]);
    }