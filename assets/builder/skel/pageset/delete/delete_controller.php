<?=$pageset->getPageByType("delete")->getMethodDecSource()?>
    {
        if ($id = $this->request["id"]) {
            table("<?=$table->getName()?>")->deleteById($id);
        }
        return redirect("id://<?=$controller->getIndexPage()->getLocalPage()?>", array("back"=>"1"));
    }
