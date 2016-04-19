
    //-------------------------------------
    // Action: logout
    public function act_logout () {
        
        $this->context("c");
        
        // ログアウト処理
        $this->c_<?=$c["account"]?>_auth->logout();
        
        redirect("page:index.index");
    }