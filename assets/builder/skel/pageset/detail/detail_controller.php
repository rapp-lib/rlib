<?=$pageset->getPageByType("detail")->getMethodDecSource()?>
    {
        $this->vars["t"] = table("<?=$table->getName()?>")<?=$pageset->getTableChainSource("find")?>->selectById($this->input["id"]);
        if ( ! $this->vars["t"]) return $this->response("notfound");
    }
