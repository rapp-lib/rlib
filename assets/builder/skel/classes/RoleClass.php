<#?php
namespace R\App\Role;

/**
 * @role
 */
class <?=$role->getClassName()?> extends Role_App
{
    /**
     * @override
     */
    public function loginTrial ($params)
    {
        $result = false;
        if ($params["login_id"]) {
            if ($params["login_id"]=="test" && $params["login_pass"]=="cftyuhbvg") {
                $result = array("id"=>1, "privs"=>array());
            }
        }
        return $result;
    }
    /**
     * @override
     */
    public function onLoginRequired ($required)
    {
<?php if ($role->getLoginController()): ?>
        return redirect("id://<?=$role->getLoginController()->getName()?>.login",array(
            "redirect" => $this->isLogin() ? "" : "".app()->http->getServedRequest()->getUri(),
        ));
<?php endif; ?>
    }
    /**
     * @override
     */
    public function onLogin ()
    {
        app()->session->regenerateId(true);
    }
    /**
     * @override
     */
    public function onLogout ()
    {
        app()->session->destroy();
    }
    /**
     * @override
     */
    public function onAccess ()
    {
    }
}
