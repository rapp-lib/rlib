<?=$pageset->getPageByType("delete")->getMethodDecSource()?>
    {
        if ($id = $this->input["id"]) {
            table("<?=$table->getName()?>")<?=$pageset->getTableChainSource("find")?>->deleteById($id);
        }
        return $this->redirect("id://<?=$pageset->getBackPage()->getFullPage($pageset->getPageByType("delete"))?>", array("back"=>"1"));
    }
