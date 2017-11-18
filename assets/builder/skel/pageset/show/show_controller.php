    /**
     * 検索フォーム
     */
    protected static $form_search = array(
        "receive_all" => true,
        "search_page" => "<?=$pageset->getPageByType("list")->getFullPage()?>",
        "search_table" => "<?=$table->getName()?>",
        "fields" => array(
<?php if ($get_param = $controller->getGetparam()): ?>
            "<?=$get_param["field_name"]?>" => array("search"=>"where", "target_col"=>"<?=$get_param["field_name"]?>"),
<?php endif; ?>
            "freeword" => array("search"=>"word", "target_col"=>array(<?php foreach ($controller->getListCols() as $col): ?><?php if ($col->getAttr("def.type")=="text"): ?>"<?=$col->getName()?>",<?php endif; ?><?php endforeach; ?>)),
            "p" => array("search"=>"page", "volume"=>20),
            "sort" => array("search"=>"sort", "default"=>"<?=$table->getOrdCol() ? $table->getOrdCol()->getName() : $table->getIdCol()->getName()?>"),
        ),
    );
<?=$pageset->getPageByType("list")->getMethodDecSource()?>
    {
        if ($this->input["back"]) {
            $this->forms["search"]->restore();
        } elseif ($this->forms["search"]->receive($this->input)) {
            $this->forms["search"]->save();
        }
        $this->vars["ts"] = $this->forms["search"]->search()<?=$controller->getTableChain("find")?>->select();
<?php if ($get_param = $controller->getGetparam()): ?>
        if ( ! $this->forms["search"]["<?=$get_param["field_name"]?>"]) return $this->response("notfound");
<?php endif; ?>
    }
<?=$pageset->getPageByType("detail")->getMethodDecSource()?>
    {
        $this->vars["t"] = table("<?=$table->getName()?>")<?=$controller->getTableChain("find")?>->selectById($this->input["id"]);
        if ( ! $this->vars["t"]) return $this->response("notfound");
    }
