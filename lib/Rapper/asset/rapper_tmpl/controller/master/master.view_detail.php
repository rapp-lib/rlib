
    //-------------------------------------
    // Action: 詳細表示
    public function act_view_detail () {
        
        $this->context("c");
        
        // idの指定
        $this->c->id($_REQUEST["id"]);
        
        // 登録データの取得
        $this->vars["t"] =<?=$model_obj?>->get_by_id($this->c->id());
        
        // 既存データの取得ができない場合の処理
        if ( ! $this->vars["t"]) {
                
            $this->c->id(false);
        
            redirect("page:.view_list");
        }
    }