    /**
     * 検索フォーム
     */
    protected static $form_search = array(
        "search_page" => ".view_list",
        "search_table" => "<?=$table->getName()?>",
        "fields" => array(
            "freeword" => array("search"=>"word", "target_col"=>array(<?php foreach ($controller->getListCols() as $col): ?>"<?=$col->getName()?>",<?php endforeach; ?>)),
            "p" => array("search"=>"page", "volume"=>20),
            "order" => array("search"=>"sort", "default"=>"<?=$table->getIdCol()->getName()?>@ASC"),
        ),
    );
<?=$pageset->getPageByType("list")->getMethodDecSource()?>
    {
        if ($this->forms["search"]->receive()) {
            $this->forms["search"]->save();
        } elseif ($this->request["back"]) {
            $this->forms["search"]->restore();
        }
        $this->vars["ts"] = $this->forms["search"]->search()->select();
    }
<?=$pageset->getPageByType("detail")->getMethodDecSource()?>
    {
        $this->vars["t"] = table("<?=$table->getName()?>")->selectById($this->request["id"]);
    }
