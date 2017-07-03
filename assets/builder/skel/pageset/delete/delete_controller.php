<?=$pageset->getPageByType("delete")->getMethodDecSource()?>
    {
        if ($id = $this->input["id"]) {
            table("<?=$table->getName()?>")->deleteById($id);
        }
        return $this->redirect("id://<?=$controller->getIndexPage()->getLocalPage()?>", array("back"=>"1"));
    }
