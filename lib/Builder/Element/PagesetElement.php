<?php
namespace R\Lib\Builder\Element;

class PagesetElement extends Element_Base
{
    public function init ()
    {
        $table = $this->getController()->getTable();
        if ($this->getSkelConfig("use_table") && ! $table) {
            report_error("Tableの指定が必須です",array(
                "controller" => $this->getController(),
                "pageset" => $this,
            ));
        }
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
    public function getSkelConfig ($key)
    {
        return $this->getSchema()->getConfig($this->getTemplateEntry().".".$key);
    }
    public function getTitle ()
    {
        $title = $this->getParent()->getLabel();
        if ($this->getParent()->getIndexPageset() !== $this) {
            if ($label = $this->getSkelConfig("label")) $title .= " ".$label;
        }
        return $title;
    }
    /**
     * 複合的な属性を取得する
     */
    public function getFlg ($flg)
    {
        $controller = $this->getParent();
        if ($flg=="is_mypage") return $controller->getFlagAttr("is_mypage");
        if ($flg=="is_master") return $this->getAttr("is_master");
        if ($flg=="is_edit") {
            if ($controller->getFlagAttr("is_mypage") && $controller->getRole()) {
                if ($role_table = $controller->getRole()->getAuthTable()) {
                    return $controller->getTable()->getName() == $role_table->getName();
                }
            }
            return false;
        }
    }
    /**
     * パラメータとして受け付けるField情報の取得
     */
    public function getParamFields ()
    {
        $param_fields = $this->getAttr("param_fields") ?: array();
        foreach ($param_fields as $field_name => & $param_field) $param_field["field_name"] = $field_name;
        return $param_fields;
    }
    public function getParamFieldByCol ($col)
    {
        $param_fields = $this->getParamFields();
        return $param_fields[$col->getName()] ?: null;
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
    /**
     * Tableのクエリ組み立てChainのPHPコードを取得
     */
    public function getTableChainSource ($type)
    {
        $append = "";
        if ($type=="find") {
            if ($this->getFlg("is_mypage")) $append .= '->findMine()';
        } elseif ($type=="save") {
            if ($this->getFlg("is_mypage")) $append .= '->saveMine()';
            else $append .= '->save()';
        }
        return $append;
    }

// -- リンク参照機能

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
        // ControllerのIndexではない場合（Master内のForm等）はControllerのIndex
        if ($this != ($index_pageset = $this->getController()->getIndexPageset())) {
            return $index_pageset->getIndexPage();
        }
        // LinkToで指定されている場合は優先
        if (($links = $this->getLinkTo()) && $links["back"]) {
            return $links["back"]["page"];
        }
        // Linkの参照元がある場合
        foreach ($this->getSchema()->getControllers() as $controller_from) {
            foreach ($controller_from->getPagesets() as $pageset_from) {
                foreach ($pageset_from->getLinkTo() as $link_from) {
                    if ($link_from["controller"] == $this->getController()) {
                        return $controller_from->getIndexPage();
                    }
                }
            }
        }
        // その他の場合はRoleのIndexを参照
        return $this->getController()->getRole()->getIndexController()->getIndexPage();
    }
    /**
     * リンク先情報の取得
     */
    public function getLinkTo ()
    {
        // IndexPagesetでなければController外にLinkは張らない
        if ($this != $this->getController()->getIndexPageset()) return array();
        $links = (array)$this->getController()->getAttr("link_to");
        foreach ($links as & $link) {
            // controllerの解決
            if (is_string($link)) $link = array("to" => $link);
            $link["controller"] = $this->getSchema()->getControllerByName($link["to"]);
            if ($link["controller"]) $link["page"] = $link["controller"]->getIndexPage();
            // labelの解決
            $link["label"] = $link["label"] ?: $link["controller"]->getLabel();
            // 相互のテーブル関係の確認
            if (($table = $this->getController()->getTable()) && $link["controller"]->getTable()) {
                // Having：Categoryの一覧 → Articleの一覧
                //    ${Article.fkey_col_name}=${Category.id_value}
                $having_fkey_col = $link["controller"]->getTable()
                    ->getColByAttr("def.fkey_for", $table->getName());
                if ($having_fkey_col) {
                    $link["table_rel"] = "having";
                    if ( ! $link["param_name"]) $link["param_name"] = $having_fkey_col->getName();
                }
                // Even：Categoryの一覧 → Categoryの詳細
                //    ${Category.id_col_name}=${Category.id_value}
                $even_id_col = $link["controller"]->getTable() == $table
                    ? $table->getColByAttr("def.id") : null;
                if ($even_id_col) {
                    $link["table_rel"] = "even";
                    if ( ! $link["param_name"]) $link["param_name"] = $even_id_col->getName();
                }
            }
            // Linkコードの取得クロージャ
            $link["getLinkSource"] = function($o)use($link){
                $page_name = $link["page"]->getFullPage($o["from_page"]);
                $param = $o["param"] ?: '$t["id"]';
                if ($type=="redirect") {
                    $source .= 'return $this->redirect("id://'.$page_name.'"';
                    if ($link["param_name"]) $source .= ', array("'.$link["param_name"].'"=>"'.$param.'")';
                    $source .= ');';
                } else {
                    if ( ! $o["param"]) $param = '$t.id';
                    $source .= '{{"'.$page_name.'"|page_to_url';
                    if ($link["param_name"]) $source .= ':["'.$link["param_name"].'"=>'.$param.']';
                    $source .= '}}';
                }
                return $source;
            };
        }
        return $links;
    }
}
