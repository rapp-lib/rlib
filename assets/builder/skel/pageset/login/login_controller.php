    /**
     * ログインフォーム
     */
    protected static $form_login = array(
        "form_page" => ".index",
        "fields" => array(
            "login_id",
            "login_pw",
            "redirect",
        ),
        "rules" => array(
        ),
    );
<?=$pageset->getPageByType("login")->getMethodDecSource()?>
    {
        if ($this->forms["login"]->receive($this->input)) {
            if ($this->forms["login"]->isValid()) {
                // ログイン処理
                $priv = app()->user->authenticate("<?=$controller->getRole()->getName()?>", array(
                    "type" => "idpw",
                    "login_id" => $this->forms["login"]["login_id"],
                    "login_pw" => $this->forms["login"]["login_pw"],
                ));
                if ($priv) {
                    app()->user->setPriv("<?=$controller->getRole()->getName()?>",$priv);
                    return $this->redirect($this->forms["login"]["redirect"] ?: "id://<?=$controller->getRole()->getIndexController()->getIndexPage()->getFullPage($pageset->getPageByType("login"))?>");
                } else {
                    $this->vars["login_error"] = true;
                }
            }
        // 転送先の設定
        } elseif ($redirect = $this->input["redirect"]) {
            $this->forms["login"]["redirect"] = htmlspecialchars_decode($redirect);
        }
    }
<?=$pageset->getPageByType("exit")->getMethodDecSource()?>
    {
        // ログアウト処理
        app()->user->setPriv("<?=$controller->getRole()->getName()?>",false);
        // ログアウト後の転送処理
        return $this->redirect("id://<?=$controller->getIndexPage()->getFullPage($pageset->getPageByType("exit"))?>");
    }
