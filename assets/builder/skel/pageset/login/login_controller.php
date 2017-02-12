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
<?=$pageset->getPageByType("login")->getMethodDecSource()?>
    {
        if ($this->forms["login"]->receive()) {
            if ($this->forms["login"]->isValid()) {
                // ログイン処理
                if (auth("<?=$controller->getRole()->getName()?>")->login($this->forms["login"])) {
                    // ログイン成功時の転送処理
                    if ($redirect = $this->forms["login"]["redirect"]) {
                        return redirect("url:".$redirect);
                    } else {
                        return redirect("page:<?=$controller->getRole()->getIndexController()->getIndexPage()->getFullPage($pageset->getPageByType("login"))?>");
                    }
                } else {
                    $this->vars["login_error"] = true;
                }
            }
        // 転送先の設定
        } elseif ($redirect = $this->request["redirect"]) {
            $this->forms["login"]["redirect"] = htmlspecialchars_decode($redirect);
        }
    }
<?=$pageset->getPageByType("exit")->getMethodDecSource()?>
    {
        // ログアウト処理
        auth("<?=$controller->getRole()->getName()?>")->logout();
        // ログアウト後の転送処理
        return redirect("page:<?=$controller->getIndexPage()->getFullPage($pageset->getPageByType("exit"))?>");
    }
