<?=$pageset->getPageByType("detail")->getMethodDecSource()?>
    {
<?php if ($pageset->getFlg("is_edit")): ?>
        $this->vars["t"] = table("<?=$table->getName()?>")<?=$pageset->getTableChainSource("find")?>->selectOne();
<?php else: ?>
        $this->vars["t"] = table("<?=$table->getName()?>")<?=$pageset->getTableChainSource("find")?>->selectById($this->input["id"]);
<?php endif; ?>
        if ( ! $this->vars["t"]) return $this->response("notfound");
    }
