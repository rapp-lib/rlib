<?php
namespace R\Lib\Builder\Element;

use R\Lib\Builder\CodeRenderer;

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

// -- 検索フォーム

    /**
     * 検索フォームに表示するColの取得
     */
    public function getSearchFields ()
    {
        $fields = array();
        $controller = $this->getParent();
        $table = $controller->getTable();
        foreach ($controller->getInputCols() as $col) {
            $field_name = $col->getName();
            // param_fieldsの指定
            if ($param_field = $this->getParamFieldByName($field_name)) {
                if ($param_field["type"]=="depend") {
                    $fields[$field_name] = array(
                        "field_name"=>$field_name,
                        "col"=>$col,
                        "search_type"=>"depend",
                        "is_fixed"=>true,
                    );
                }
            }
            // archive_fieldの指定
            if ($field_name == $controller->getAttr("archive_field")) {
                $fields[$field_name] = array(
                    "field_name"=>$field_name,
                    "col"=>$col,
                    "search_type"=>"archive",
                    "archive_type"=>"archive",
                    "is_fixed"=>true,
                );
                if ($col->getEnumSet()) {
                    $fields[$field_name]["archive_type"] = "enum";
                } elseif (in_array($col->getAttr("def.type"), array("date", "datetime"))) {
                    $fields[$field_name]["archive_type"] = "year";
                }
            }
        }
        // search_fieldsの指定
        foreach ((array)$controller->getAttr("search_fields") as $field_name=>$field) {
            // field_nameの補完
            if (is_numeric($field_name) && is_string($field)) {
                $field_name = $field;
                $field = array("field_name"=>$field_name);
            }
            if (is_string($field)) {
                $field = array("field_name"=>$field_name, "search_type"=>$field);
            }
            // colの補完
            if ( ! $field["col"]) $field["col"] = $field_name;
            $field["col"] = $table->getColByName($field["col"]);
            // search_typeの補完
            if ( ! $field["search_type"]) {
                if (in_array($field["col"]->getAttr("type"), array("text", "textarea"))) {
                    $field["search_type"] = "word";
                } elseif (in_array($field["col"]->getAttr("def.type"), array("date", "datetime"))) {
                    $field["search_type"] = "date_range";
                } else {
                    $field["search_type"] = "where";
                }
            }
            $fields[$field_name] = $field;
        }
        return $fields;
    }
    /**
     * 固定の検索項目の取得
     */
    public function getFixedSearchFields ()
    {
        $fields = $this->getSearchFields();
        return array_filter($fields, function($field){
            return $field["is_fixed"];
        });
    }
    /**
     * 固定の検索項目のパラメータ取得
     */
    public function getFixedSearchParamSource ()
    {
        $var_name = '$forms.search';
        $params = array();
        foreach ($this->getFixedSearchFields() as $field) {
            $params[] = $var_name.".".$field["field_name"]."=>".$field["field_name"];
        }
        return $params ? "[".implode(", ",$params)."]" : "";
    }
    /**
     * 検索入力HTMLソースの取得
     */
    public function getSearchInputSource ($field)
    {
        $o = array("page"=>$this->getIndexPage());
        if ($field["search_type"]=="date_range") {
            $o["type"] = "date";
            $o_start = $o_end = $o;
            $o_start["name"] = $field["field_name"]."_start";
            $o_end["name"] = $field["field_name"]."_end";
            return $field["col"]->getInputSource($o_start)
                ." &#xFF5E; ".$field["col"]->getInputSource($o_end);
        } elseif ($field["search_type"]=="archive") {
            $source = "";
            $o["type"] = "hidden";
            $source .= $field["col"]->getInputSource($o);
            $archive_src = 'array()';
            if ($field["archive_type"]=="enum") {
                $archive_src = '$enum["'.$field["col"]->getEnumSet()->getFullName().'"]';
            } elseif ($field["archive_type"]=="year") {
                $archive_src = 'range(date("Y")-3, date("Y")+3)';
            }
            $source .= "\n".str_repeat("    ",3).'{{foreach '.$archive_src.' as $k=>$v}}';
            $source .= "\n".str_repeat("    ",4).'<a '
                .'href="{{$forms.search->getSearchPageUrl([\''.$field["field_name"].'\'=>$k])}}" '
                .'class="enum'.'{{if $forms.search.'.$field["field_name"].' == $k}} current{{/if}}"'
                .'>{{$v}}</a>';
            $source .= "\n".str_repeat("    ",3).'{{/foreach}}';
            $source .= "\n".str_repeat("    ",2);
            return $source;
        } elseif ($field["search_type"]=="depend") {
            $o_hidden = $o_show = $o;
            $o_hidden["type"] = "hidden";
            $o_show["var_name"] = '$forms.search';
            return $field["col"]->getInputSource($o_hidden).$field["col"]->getShowSource($o_show);
        }
        if ($field["search_type"]=="word") {
            $o["type"] = "text";
        }
        return $field["col"]->getInputSource($o);
    }
    /**
     * $form_searchの定義行の取得
     */
    public function getSearchFormFieldDefSource ($field)
    {
        $field_name = $field["field_name"];
        $col_name = $field["col"]->getName();
        $lines = array();
        if ($field["search_type"]=="date_range") {
            $lines[$field_name."_start"] = array("search"=>"where", "target_col"=>$col_name." >=");
            $lines[$field_name."_end"] = array("search"=>"where", "target_col"=>$col_name." - INTERVAL 1 DAY <");
        } elseif ($field["search_type"]=="depend") {
            $lines[$field_name] = array("search"=>"where", "target_col"=>$col_name);
        } elseif ($field["search_type"]=="archive") {
            if ($field["archive_type"]=="year") $col_name = "DATE_FORMAT(".$col_name.", '%Y')";
            $lines[$field_name] = array("search"=>"where", "target_col"=>$col_name);
        } else {
            $lines[$field_name] = array("search"=>$field["search_type"], "target_col"=>$col_name);
        }
        return CodeRenderer::elementLines(3, $lines);
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
        // param_fields.<type>.<field_name>が指定されている場合
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
        // pagesetでidの引き渡しが要件になっていて、指定がない場合、id=での受け渡しを補完
        if ( ! $param_fields) {
            $params_config = $this->getSkelConfig("params");
            if ($params_config["id"]) {
                $param_fields["id"] = array("type"=>"id", "field_name"=>"id", "param_name"=>"id");
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

// -- archive_field

    /**
     * リンク先Pagesetへのリンク記述コードを取得
     */
    public function getArchiveField ()
    {
        // archive_fieldが指定されている場合
        if ($field_name = $this->getAttr("archive_field")) {
            $param_fields[$field_name] = array(
                "type"=>"archive",
                "field_name"=>$field_name,
                "param_name"=>$field_name,
            );
        }
    }

}
