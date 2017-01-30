    /**
     * ログインフォーム
     */
    protected static $form_login = array(
        "form_page" => ".index",
        "fields" => array(
            "login_id",
            "login_pass",
            "redirect",
        ),
        "rules" => array(
        ),
    );
    /**
     * @page
     * @title <?=$controller_label?> TOP
     */
    public function act_index ()
    {
        return redirect("page:.login");
    }
    /**
     * @page
     * @title <?=$controller_label?> ログインフォーム
     */
    public function act_login ()
    {
        if ($this->forms["login"]->receive()) {
            if ($this->forms["login"]->isValid()) {
                // ログイン処理
                if (app()->auth->login("<?=$c["access_as"]?>", $this->forms["login"])) {
                    // ログイン成功時の転送処理
                    if ($redirect = $this->forms["login"]["redirect"]) {
                        return redirect("url:".$redirect);
                    } else {
                        return redirect("page:<?=builder()->getSchema()->getController($c["name"])->getRole()->getIndexController()->getName()?>.index");
                    }
                } else {
                    $this->vars["login_error"] = true;
                }
            }
        // 転送先の設定
        } elseif ($redirect = $this->request["redirect"]) {
            $this->forms["login"]["redirect"] = sanitize_decode($redirect);
        }
    }
    /**
     * @page
     * @title <?=$controller_label?> ログアウト
     */
    public function act_logout ()
    {
        // ログアウト処理
        app()->auth->logout("<?=$c["access_as"]?>");
        // ログアウト後の転送処理
        return redirect("page:.login");
    }