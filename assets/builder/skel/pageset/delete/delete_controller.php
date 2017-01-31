<?=$pageset->getPageByType("delete")->getMethodDecSource()?>
    {
        if ($id = $this->request["id"]) {
            table("<?=$table->getName()?>")->deleteById($id);
        }
        return redirect("page:<?=$controller->getIndexPage()->getLocalPage()?>", array("back"=>"1"));
    }
