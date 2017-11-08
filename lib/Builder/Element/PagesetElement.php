<?php
namespace R\Lib\Builder\Element;

class PagesetElement extends Element_Base
{
    public function init ()
    {
        // Page登録
        $page_configs = (array)$this->getSchema()->getConfig($this->getTemplateEntry().".pages");
        foreach ($page_configs as $page_type => $page_config) {
            $page_name = $this->getName()==$page_type ? $page_type : $this->getName()."_".$page_type;
            $page_attrs = array("type"=>$page_type);
            $this->children["page"][$page_name] = new PageElement($page_name, $page_attrs, $this);
        }
        // Mail登録
        if ($this->getAttr("use_mail")) {
            $controller_name = $this->getParent()->getName();
            // 管理者通知メール
            $this->children["mail"][] = new MailElement($controller_name.".admin", array(
                "type" => "admin",
            ), $this);
            // 自動返信メール
            if ($this->getParent()->getTable()) {
                if ($mail_col = $this->getParent()->getTable()->getColByAttr("def.mail")) {
                    $this->children["mail"][] = new MailElement($controller_name.".reply", array(
                        "type" => "reply",
                        "mail_col_name" => $mail_col->getName(),
                    ), $this);
                }
            }
        }
    }
    public function getTemplateEntry ()
    {
        return "pageset.".$this->getAttr("type");
    }
    public function getLabel ()
    {
        return $this->getParent()->getLabel();
    }
    /**
     * @getter Mail
     */
    public function getMails ()
    {
        return (array)$this->children["mail"];
    }
    public function getMailByType ($type)
    {
        foreach ($this->getMails() as $mail) if ($mail->getAttr("type")===$type) return $mail;
        return null;
    }
    /**
     * @getter Page
     */
    public function getPages ()
    {
        return (array)$this->children["page"];
    }
    public function getPageByType ($type)
    {
        foreach ($this->getPages() as $page) {
            if ($page->getAttr("type")==$type) {
                return $page;
            }
        }
        report_error("指定したTypeのPageがありません",array(
            "type" => $type,
            "pageset" => $this,
        ));
        return null;
    }
    public function getIndexPage ()
    {
        //TODO: 1番目のPageが取得されてしまうので、制御を加える
        foreach ($this->getPages() as $page) {
            return $page;
        }
        return null;
    }
    public function getBackPage ()
    {
        $parent_index_page = $this->getController()->getIndexPage();
        if ($parent_index_page->getParent() != $this) {
            return $parent_index_page;
        }
        return $this->getController()->getRole()->getIndexController()->getIndexPage();
    }
    /**
     * @getter Controller
     */
    public function getController ()
    {
        return $this->getParent();
    }
    /**
     * ControllerClass中のPHPコードを取得
     */
    public function getControllerSource ()
    {
        $controller = $this->getParent();
        $role = $controller->getRole();
        $table = $controller->getTable();
        return $this->getSchema()->fetch($this->getTemplateEntry().".controller", array(
            "pageset"=>$this, "controller"=>$controller, "role"=>$role, "table"=>$table));
    }
}
