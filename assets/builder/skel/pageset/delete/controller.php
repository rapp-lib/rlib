    /**
     * @page
     * @title <?=$controller_label?> 削除
     */
    public function act_delete ()
    {
        if ($id = $this->request["id"]) {
            <?=$__table_instance?>->deleteById($id);
        }
        return redirect("page:.view_list", array("back"=>"1"));
    }