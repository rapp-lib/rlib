
    //-------------------------------------
    // Action: entry_confirm
    public function act_entry_confirm () {

        $this->context("c",1,true);

        $this->c->input($_REQUEST["c"]);
        $this->c->errors(false,array());

        // ログイン処理
        $this->c_<?=$c["account"]?>_auth->login(
                $this->c->input("login_id"),
                $this->c->input("login_pass"));

        // ログインエラー時の処理
        if ( ! $this->c_<?=$c["account"]?>_auth->id()) {

            $errmsg =registry("Label.errmsg.user.<?=$c["account"]?>_login_failed");
            $this->c->errors("login_id",$errmsg);

            redirect("page:.entry_form");
        }

        // 転送先の指定があればそちらを優先
        if ($this->c->session("redirect_to")) {

            redirect($this->c->session("redirect_to"));
        }

        redirect("page:index.index");
    }