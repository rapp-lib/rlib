
    //-------------------------------------
    // Action: entry_form
    public function act_entry_form () {
        
        $this->context("c",1,true);
        
        // 転送先指定の保存
        if ($_REQUEST["redirect_to"]) {
            
            $redirect_to =sanitize_decode($_REQUEST["redirect_to"]);
            $this->c->session("redirect_to",$redirect_to);
        }
    }