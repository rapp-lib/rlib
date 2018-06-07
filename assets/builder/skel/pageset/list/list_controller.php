    /**
     * 検索フォーム
     */
    protected static $form_search = array(
        "receive_all" => true,
        "search_page" => "<?=$pageset->getPageByType("list")->getFullPage()?>",
        "search_table" => "<?=$table->getName()?>",
        "fields" => array(
<?php foreach ($pageset->getSearchFields() as $field): ?>
<?=         $pageset->getSearchFormFieldDefSource($field); ?>
<?php endforeach; ?>
            "p" => array("search"=>"page", "volume"=>20),
            "sort" => array("search"=>"sort", "cols"=>array("<?=implode('", "', $controller->getSortatbleFieldNames())?>")),
        ),
    );
<?php if ($controller->getAttr("bulk_actions")): ?>
    /**
     * 一括操作フォーム
     */
    protected static $form_bulk = array(
        "form_page" => "<?=$pageset->getPageByType("list")->getFullPage()?>",
        "fields" => array(
            "action",
            "items.*.id",
        ),
    );
<?php endif; ?>
<?=$pageset->getPageByType("list")->getMethodDecSource()?>
    {
<?php if ($bulk_actions = $controller->getAttr("bulk_actions")): ?>
        if ($this->forms["bulk"]->receive($this->input)) {
            $items = array_filter((array)$this->forms["bulk"]["items"], function($item){
                return $item["id"];
            });
            if ($this->forms["bulk"]["action"]=="delete" && $items) {
                foreach ($items as $item) table("<?=$table->getName()?>")<?=$pageset->getTableChainSource("find")?>->deleteById($item["id"]);
                $this->flash->success(___("削除しました"));
            }
            return $this->redirect("id://.", array("back"=>"1"));
        }
<?php endif; ?>
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
