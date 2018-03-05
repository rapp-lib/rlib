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
        $page_configs = (array)$this->getSkelConfig("pages");
        $index_page_type = $this->getSkelConfig("index_page");
        foreach ($page_configs as $page_type => $page_config) {
            $page_name = $this->getName();
            if ($index_page_type!=$page_type) $page_name .= "_".$page_type;
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
     * @getter Controller
     */
    public function getController ()
    {
        return $this->getParent();
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

// -- source

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

    private $links_to = null;
    /**
     * リンク先情報の取得
     */
    public function getLinkTo ()
    {
        if ($this->links_to !== null) return $this->links_to;
        // IndexPagesetでなければController外にLinkは張らない
        if ($this != $this->getController()->getIndexPageset()) return $this->links_to = array();
        $links = (array)$this->getController()->getAttr("link_to");
        foreach ($links as & $link) {
            if (is_string($link)) $link = array("to" => $link);
            // toからcontrollerの解決
            if (preg_match('!^([^\.]+)\.([^\.]+)$!', $link["to"], $_)) {
                $link["controller"] = $this->getSchema()->getControllerByName($_[1]);
            } else {
                $link["controller"] = $this->getSchema()->getControllerByName($link["to"]);
            }
            if ( ! $link["controller"]) {
                report_error("LinkToで指定されたControllerが不正です", array(
                    "to" => $link["to"],
                ));
            }
            // toからpagesetの解決
            if (preg_match('!^([^\.]+)\.([^\.]+)$!', $link["to"], $_)) {
                $link["pageset"] = $link["controller"]->getPagesetByType($_[2]);
            } else {
                $link["pageset"] = $link["controller"]->getIndexPageset();
            }
            if ( ! $link["pageset"]) {
                report_error("LinkToで指定されたPagesetが不正です", array(
                    "to" => $link["to"],
                    "pagesets" => $link["controller"]->getPagesets(),
                ));
            }
            // labelの解決
            $link["label"] = $link["label"] ?: $link["controller"]->getLabel();
            // レコード単位での依存関係を解決
            if ( ! $link["by_record"]) {
                foreach ($link["pageset"]->getParamFields() as $param_field) {
                    if ( ! $this->getParamFieldByName($param_field["field_name"])) {
                        $link["by_record"] = true;
                    }
                }
            }
        }
        return $this->links_to = $links;
    }

    private $links_form = null;
    /**
     * リンク元情報の取得
     */
    public function getLinkFrom ()
    {
        if ($this->links_form !== null) return $this->links_form;
        $links_form = array();
        foreach ($this->getSchema()->getControllers() as $from_controller) {
            foreach ($from_controller->getPagesets() as $from_pageset) {
                foreach ($from_pageset->getLinkTo() as $link) {
                    if ($link["pageset"]==$this) {
                        $links_form[] = $link;
                    }
                }
            }
        }
        return $this->links_form = $links_form;
    }

// -- param_fields

    /**
     * パラメータとして受け付けるField情報の取得
     */
    public function getParamFields ($types=array())
    {
        $param_fields = array();
        // Schema上でのparam_fields.<type>.<field_name>が指定されている場合
        foreach ((array)$this->getAttr("param_fields") as $field_type => $fields) {
            // param_fieldsの指定が配列ではなくfield_nameのみである場合に対応
            if ($fields && ! is_array($fields)) $fields = array($fields=>array());
            // 配列構造を補完する
            foreach ((array)$fields as $field_name => $param_field) {
                if ( ! $param_field["field_name"]) $param_field["field_name"] = $field_name;
                if ( ! $param_field["type"]) $param_field["type"] = $field_type;
                // 主にtype=appendで*.*形式により、Assoc内へのパラメータ引き渡し行う場合
                if (preg_match('!\.!', $field_name)) {
                    $parts = explode(".", $param_field["field_name"]);
                    $param_field["field_name"] = $parts[0];
                    $param_field["assoc_field_name"] = $parts[1];
                    $param_field["param_name"] = $parts[0]."[".$parts[1]."]";
                } else {
                    $param_field["param_name"] = $param_field["field_name"];
                }
                $param_fields[$param_field["field_name"]] = $param_field;
            }
        }
        // Typeの指定があれば絞り込んで返す
        if ($types) $param_fields = array_filter($param_fields, function($param_field)use($types){
            return in_array($param_field["type"], is_array($types) ? $types : array($types));
        });
        return $param_fields;
    }
    public function getParamFieldByName ($name)
    {
        $param_fields = $this->getParamFields();
        return $param_fields[$name] ?: null;
    }

    /**
     * リンク先Pagesetへのリンク記述コードを取得
     */
    public function getLinkSource ($type, $from_pageset, $o=array())
    {
        // リンク箇所のコンテキスト変数名
        $form_name = $o["form_name"] ?: null;
        $record_name = $o["record_name"] ?: null;
        // 相互のテーブル
        $from_table = $from_pageset->getController()->getTable();
        $to_table = $this->getController()->getTable();
        foreach ($this->getParamFields() as $param_field) {
            $field_name = $param_field["field_name"];
            $param_name = $param_field["param_name"];
            // recordの指定がある場合、必ず主キーの値を受け渡す
            if ($record_name && $from_table) {
                //TODO: パラメータに該当するカラムが存在するのかどうかのチェックとスキップ
                // リンク先で同一のテーブルを参照している場合、id=で渡す
                if ($from_table==$to_table) $param_name = "id";
                // 外部キーの名前をつけて、recordの主キーを渡す
                $id_col_name = $from_table->getIdCol()->getName();
                if ($type=="redirect") $o["params"][$param_name] = $record_name.'["'.$id_col_name.'"]';
                else $o["params"][$param_name] = $record_name.'.'.$id_col_name;
            // リンク元ページも同名のパラメータを受け取っている場合
            } elseif ($from_pageset->getParamFieldByName($field_name)) {
                // Form経由で共通パラメータの引き継ぎ
                if ($type=="redirect") $o["params"][$param_name] = $form_name.'["'.$field_name.'"]';
                else $o["params"][$param_name] = $form_name.'.'.$field_name;
            }
        }
        return $this->getIndexPage()->getLinkSource($type, $from_pageset, $o);
    }
}
