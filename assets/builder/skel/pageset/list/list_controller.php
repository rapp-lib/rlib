    /**
     * 検索フォーム
     */
    protected static $form_search = array(
        "receive_all" => true,
        "search_page" => "<?=$pageset->getPageByType("list")->getFullPage()?>",
        "search_table" => "<?=$table->getName()?>",
        "fields" => array(
<?php foreach ($controller->getInputCols() as $col): ?>
<?php   if ($param_field = $pageset->getParamFieldByName($col->getName())): ?>
<?=         $col->getSearchFormFieldDefSource(array("pageset"=>$pageset, "type"=>"where"))?>
<?php   endif; ?>
<?php endforeach; ?>
<?php foreach ($controller->getSearchCols() as $col): ?>
<?=         $col->getSearchFormFieldDefSource(array("pageset"=>$pageset))?>
<?php endforeach; ?>
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
<?php foreach ($pageset->getParamFields("depend") as $param_field): ?>
        if ( ! $this->forms["search"]["<?=$param_field["field_name"]?>"]) return $this->response("badrequest");
<?php endforeach; ?>
    }
