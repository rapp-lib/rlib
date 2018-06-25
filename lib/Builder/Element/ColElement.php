<?php
namespace R\Lib\Builder\Element;

use R\Lib\Builder\CodeRenderer;

class ColElement extends Element_Base
{
    public function getLabel ()
    {
        return $this->getAttr("label");
    }
    /**
     * @getter table
     */
    public function getTable ()
    {
        return $this->getParent();
    }
    /**
     * @getter EnumSet
     */
    public function getEnumSet ()
    {
        if ($enum_set_name = $this->getAttr("enum_set_name")) {
            return $this->getParent()->getEnum()->getEnumSetByName($this->getName());
        }
        return null;
    }
    /**
     * Enum Aliasカラム名の取得
     */
    public function getEnumAliasColName ()
    {
        return $this->getName()."_label";
    }
    /**
     * 入力HTMLソースの取得
     */
    public function getInputSource ($o=array())
    {
        return $this->getSchema()->fetch("parts.col_input", array("col"=>$this, "o"=>$o));
    }
    /**
     * 表示HTMLソースの取得
     */
    public function getShowSource ($o=array())
    {
        return $this->getSchema()->fetch("parts.col_show", array("col"=>$this, "o"=>$o));
    }
    /**
     * $form_entryの定義行の取得
     */
    public function getEntryFormFieldDefSource ($o=array())
    {
        $pageset = $o["pageset"];
        if ( ! $pageset) {
            report_error("getEntryFormFieldDefSourceのパラメータにはpagesetの指定が必須です", array("col"=>$this, "params"=>$o));
        }
        $controller = $pageset->getParent();

        $name = $o["name_parent"] ? $o["name_parent"].".".$this->getName() : $this->getName();
        $def = array();
        $def["label"] = $this->getAttr("label");
        if ( ! $this->hasColDef()) $def["col"] = false;
        if ($this->getAttr("type")=="file") $def["storage"] = "public";
        $lines = array();
        $lines[$name] = $def;

        // type=assocに関わる定義を追記
        if ($this->getAttr("type")==="assoc"){
            if ( ! $this->getAttr("def.assoc.single") && ($pageset->getFlg("is_master") || $pageset->getFlg("is_edit"))){
                $lines[] = $this->getName().'.*.'.$this->getAssocTable()->getIdCol()->getName();
            }
            $assoc_ord_col = $this->getAssocTable()->getOrdCol();
            if ($assoc_ord_col && ! $assoc_ord_col->getAttr("type")){
                $lines[] = $this->getName().'.*.'.$assoc_ord_col->getName();
            } else {
                $lines[$this->getName().".*.ord_seq"] = array("col"=>false);
            }
        }
        $source = CodeRenderer::elementLines(3, $lines);
        // assoc下位の定義を追記
        if ($this->getAttr("type")==="assoc"){
            foreach ($controller->getAssocInputCols($this) as $assoc_col) {
                $source .= $assoc_col->getEntryFormFieldDefSource(array(
                    "pageset" => $pageset,
                    "name_parent" => $this->getName().".*",
                ));
            }
        }
        return $source;
    }
    /**
     * メール表示用PHPソースの取得
     */
    public function getMailSource ($o=array())
    {
        return $this->getSchema()->fetch("parts.col_mail", array("col"=>$this, "o"=>$o));
    }
    /**
     * $colsの定義行の取得
     */
    public function getColDefSource ($o=array())
    {
        if ( ! $this->hasColDef()) return;
        $def = (array)$this->getAttr("def");
        $def["comment"] = $this->getAttr("label");
        if ($this->getAttr("type")=="checklist" && $def["type"]=="text" && ! $def["format"]) {
            $def["format"] = "json";
        }
        return CodeRenderer::elementLine(2, $this->getName(), $def);
    }
    /**
     * $colsの定義行の取得
     */
    public function getAliasDefSource ($o=array())
    {
        if ( ! $this->hasColDef()) return;
        // aliasesで記述されているものを登録
        $def = (array)$this->getAttr("aliases");
        // enum参照先を登録
        if ($enum_set = $this->getEnumSet()) {
            $def[$this->getEnumAliasColName()]["enum"] = $enum_set->getFullName();
            if ($this->getAttr("type")=="checklist") {
                $def[$this->getEnumAliasColName()]["array"] = true;
            }
        }
        // 外部キーであれば参照先を登録
        if ($fkey_for = $this->getAttr("def.fkey_for")) {
            $key = str_singular(snake_case($fkey_for));
            $def[$key] = array("type"=>"belongs_to", "table"=>$fkey_for);
        }
        // 主キーであれば参照元を登録
        if ($this->getAttr("def.id")) {
            foreach ($this->getSchema()->getTables() as $table) {
                foreach ($table->getCols() as $col) {
                    if ($this->getTable()->getName() == $col->getAttr("def.fkey_for")) {
                        $key = str_plural(snake_case($table->getName()));
                        $def[$key] = array("type"=>"has_many", "table"=>$table->getName());
                    }
                }
            }
        }
        if ( ! $def) return;
        return CodeRenderer::elementLine(2, $this->getName(), $def, array("breaks_first"=>1));
    }
    /**
     * form.field_def中でのrule定義行の取得
     */
    public function getRuleDefSource ($o=array())
    {
        // pageに依存するパラメータの取得
        $pageset = $o["pageset"];
        if ( ! $pageset) {
            report_error("RuleDefSourceのパラメータにはpagesetが必要です", array("col"=>$this, "params"=>$o));
        }
        $controller = $pageset->getParent();

        // nameの設定
        $name = $this->getName();
        if ($o["name_parent"]) $name = $o["name_parent"].".".$name;
        // rulesの読み込み
        $rules = array();
        foreach ((array)$this->getAttr("rules") as $type=>$params) {
            if (is_array($params)) {
                // 管理画面用の新規/編集共用フォーム
                if ($pageset->getFlg("is_master")) {
                    $id_col_name = $this->getParent()->getIdCol()->getName();
                    if ($params["if_register"]) $params["if"][$id_col_name] = false;
                    elseif ($params["if_edit"]) $params["if"][$id_col_name] = true;
                // 自分の情報の編集ページなどのedit系フォーム
                } elseif ($pageset->getFlg("is_edit")) {
                    if ($params["if_register"]) continue;
                // その他のフォーム
                } else {
                    if ($params["if_edit"]) continue;
                }
                unset($params["if_register"]);
                unset($params["if_edit"]);
            }
            if ($type==="required" && $params===true) $rules[] = $name;
            else $rules[] = array_merge(array($name, $type), (array)$params);
        }
        // Enumの指定がある場合、正当性を検証するRuleを自動追加
        if ($enum_set = $this->getEnumSet()) {
            $rules[] = array($name, "enum", "enum"=>$enum_set->getFullName());
        }
        $source = "";
        // Ruleの値を配列コードとして出力
        $source .= CodeRenderer::elementLines(3, $rules);
        // assoc配下のRuleも出力
        if ($this->getAttr("type")==="assoc") {
            foreach ($controller->getAssocInputCols($this) as $assoc_col) {
                $source .= $assoc_col->getRuleDefSource(array("pageset"=>$pageset, "name_parent"=>$name.".*"));
            }
        }
        return $source;
    }
    /**
     * assoc関係にあるTableを取得
     */
    public function getAssocTable ()
    {
        $table_name = $this->getAttr("def.assoc.table");
        return $table_name ? $this->getSchema()->getTableByName($table_name) : null;
    }
    /**
     * fkey_for関係にあるTableを取得
     */
    public function getFkeyForTable ()
    {
        $table_name = $this->getAttr("def.fkey_for");
        return $table_name ? $this->getSchema()->getTableByName($table_name) : null;
    }
    /**
     * Table上にColを持つかどうか、Colがない＝Form上のFieldのみ
     */
    public function hasColDef ()
    {
        return ! $this->getAttr("nodef");
    }
    /**
     * inputタグ属性の生成
     */
    public function getInputAttrs ()
    {
        $input_attr = "";
        foreach ((array)$this->getAttr("input") as $k=>$v) {
            $input_attr .= " ". $k.'='.CodeRenderer::smartyValue($v);
        }
        return $input_attr;
    }
}
