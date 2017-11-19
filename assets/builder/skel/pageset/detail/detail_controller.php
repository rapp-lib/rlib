<?=$pageset->getPageByType("detail")->getMethodDecSource()?>
    {
        $this->vars["t"] = table("<?=$table->getName()?>")<?=$controller->getTableChain("find")?>->selectById($this->input["id"]);
        if ( ! $this->vars["t"]) return $this->response("notfound");
    }
