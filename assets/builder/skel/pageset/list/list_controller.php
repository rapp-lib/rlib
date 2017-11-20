    /**
     * 検索フォーム
     */
    protected static $form_search = array(
        "receive_all" => true,
        "search_page" => "<?=$pageset->getPageByType("list")->getFullPage()?>",
        "search_table" => "<?=$table->getName()?>",
        "fields" => array(
<?php foreach ($pageset->getParamFields() as $param_field): ?>
            "<?=$param_field["field_name"]?>" => array("search"=>"where", "target_col"=>"<?=$param_field["field_name"]?>"),
<?php endforeach; ?>
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
        $this->vars["ts"] = $this->forms["search"]->search()<?=$pageset->getTableChainSource("find")?>->select();
<?php foreach ($pageset->getParamFields() as $param_field): ?>
<?php   if ($param_field["required"]): ?>
        if ( ! $this->forms["search"]["<?=$param_field["field_name"]?>"]) return $this->response("badrequest");
<?php   endif; ?>
<?php endforeach; ?>
    }
