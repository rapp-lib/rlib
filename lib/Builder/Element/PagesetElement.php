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
            // controller,pageの解決
            if (is_string($link)) $link = array("to" => $link);
            if (preg_match('!^([^\.]+)\.([^\.]+)$!', $link["to"], $_)) {
                $link["controller"] = $this->getSchema()->getControllerByName($_[1]);
                $link["pageset"] = $link["controller"]->getPagesetByType($_[2]);
            } else {
                $link["controller"] = $this->getSchema()->getControllerByName($link["to"]);
                $link["pageset"] = $link["controller"]->getIndexPageset();
            }
            // 相互のテーブルの関係の確認
            $from_table = $this->getController()->getTable();
            $to_table = $link["controller"]->getTable();
            // レコード単位での依存関係となるかどうかを判定
            if ($from_table && $to_table) {
                // Having：Categoryの一覧 → Articleの一覧
                if ($having_fkey_col = $to_table->getColByAttr("def.fkey_for", $from_table->getName())) {
                    $link["depend_on_record"] = $having_fkey_col->getName();
                // Even：Categoryの一覧 → Categoryの詳細
                } elseif ($to_table == $from_table) {
                    $link["depend_on_record"] = $to_table->getIdCol()->getName();
                }
            }
            // labelの解決
            $link["label"] = $link["label"] ?: $link["controller"]->getLabel();
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
        // IndexPagesetでなければController外からLinkを受け付けない
        if ($this != $this->getController()->getIndexPageset()) return $this->links_form = array();
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
        // リンク元のTableとの関係により補完
        if (($params_config = $this->getSkelConfig("params")) && $params_config["depend"]) {
            foreach ($this->getController()->getIndexPageset()->getLinkFrom() as $link) {
                $field_name = $link["depend_on_record"];
                $param_fields[$field_name] = array("type"=>"depend", "field_name"=>$field_name);
            }
        }
        // 属性の明示
        foreach ((array)$this->getAttr("param_fields") as $field_type => $fields) {
            // param_fieldsの指定が配列ではなくfield_nameのみである場合に対応
            if ($fields && ! is_array($fields)) $fields = array($fields=>array());
            // 配列構造を補完する
            foreach ((array)$fields as $field_name => $param_field) {
                if ( ! $param_field["field_name"]) $param_field["field_name"] = $field_name;
                if ( ! $param_field["type"]) $param_field["type"] = $field_type;
                $param_fields[$field_name] = $param_field;
            }
        }
        // Typeの指定があれば絞り込む
        if ($types) $param_fields = array_filter($param_fields, function($param_field)use($types){
            return in_array($param_field["type"], is_array($types) ? $types : array($types));
        });
        return $param_fields;
    }
    public function getParamFieldName ($type)
    {
        $param_fields = $this->getParamFields($type);
        $field_names = $param_fields ? array_keys($param_fields) : null;
        return $field_names ? $field_names[0] : null;
    }
    public function getParamFieldByName ()
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
        // 相互のController
        $from_controller = $from_pageset->getController();
        $to_controller = $this->getController();
        // 相互のテーブル
        $from_table = $from_controller->getTable();
        $to_table = $to_controller->getTable();
        // 相互のparams_fields.depend
        $from_depend = $from_pageset->getParamFieldName("depend");
        $to_depend = $this->getParamFieldName("depend");

        // depend共通であれば「to_dep=form[from_dep]」パラメータ付与
        if ($to_depend && $from_depend===$to_depend && $form_name) {
            $o["params"][$to_depend] = $form_name.'["'.$from_depend.'"]';
        }

        // recordの指定があればIDを渡す
        if ($record_name && $from_table) {
            // depend非共通であれば「to_dep=record[id]」パラメータ、その他は"id"固定
            $param_name = ($to_depend && $from_depend!==$to_depend) ? $to_depend : "id";
            $o["params"][$param_name] = $record_name.'["'.$from_table->getIdCol()->getName().'"]';
        }
        return $this->getIndexPage()->getLinkSource($type, $from_pageset, $o);
    }
}
