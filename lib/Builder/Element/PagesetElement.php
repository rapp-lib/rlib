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
        $controller_name = $this->getParent()->getName();
        if ($this->getAttr("use_mail")) {
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
        if ($this->getAttr("type") == "reminder") {
            // URL通知メール
            $this->children["mail"][] = new MailElement($controller_name.".mailcheck", array(
                "type" => "mailcheck",
                "mail_col_name" => "mail",
            ), $this);
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
        // TODO:Pagesetの設定でindex_pageに指定されているもの
        // foreach ($this->getPages() as $page) {
        //     if ($page->getName() == $this->getAttr("index_page")) return $page;
        // }
        // 一番はじめに登録されたもの
        foreach ($this->getPages() as $page) return $page;
        return null;
    }
    public function getBackPage ()
    {
        $controller = $this->getController();
        // ReminderであればLogin
        if ($this->getAttr("type")=="reminder") {
            $login_controller = $controller->getRole()->getLoginController();
            if ($login_controller) return $login_controller->getIndexPage();
        }
        // ControllerのIndexではない場合（Master内のForm等）はControllerのIndex
        if ($this != ($index_pageset = $controller->getIndexPageset())) {
            return $index_pageset->getIndexPage();
        }
        // Linkで参照されている場合は参照元
        if ($linked_controller = $controller->getLinkedController()) {
            return $linked_controller->getIndexPage();
        }
        // その他の場合はRoleのIndexを参照
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
