<?php require __DIR__."/../_include/controller.php"; ?>
<?=$__controller_header?>

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
     * @title <?=$controller_label?>
     */
    public function act_index ()
    {
        if ($this->forms["login"]->receive()) {
            if ($this->forms["login"]->isValid()) {
                // ログイン処理
                if (auth()->login("admin", $this->forms["login"])) {
                    // ログイン成功時の転送処理
                    if ($redirect = $this->forms["login"]["redirect"]) {
                        redirect($redirect);
                    } else {
                        redirect("/");
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
     * @title <?=$c["label"]?> ログアウト
     */
    public function act_logout ()
    {
        // ログアウト処理
        auth()->logout("admin");
        // ログアウト後の転送処理
        redirect("/");
    }
}
