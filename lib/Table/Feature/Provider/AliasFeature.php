<?php
namespace R\Lib\Table\Feature\Provider;
use R\Lib\Table\Feature\BaseFeatureProvider;

class AliasFeature extends BaseFeatureProvider
{
    /**
     * aliasが定義されていたら参照する
     */
    public function pre_on_blankCol_alias ($record, $col_name)
    {
        $found = false;
        $def = $record->getResult()->getStatement()->getQuery()->getDef();
        foreach ((array)$def->getDefAttr("aliases") as $src_col_name=>$aliases) {
            foreach ((array)$aliases as $alias_col_name=>$alias) {
                if ($alias_col_name===$col_name) {
                    if ($found) {
                        report_error("同名のaliasが重複して登録されています", array(
                            "table"=>$def->getAppTableName(),
                            "alias_col_name"=>$alias_col_name,
                            "src_col_name_1"=>$found[1],
                            "src_col_name_2"=>$src_col_name,
                        ));
                    }
                    $found = array($alias_col_name, $src_col_name, $alias);
                }
            }
        }
        return $found;
    }
    public function on_blankCol_alias ($record, $col_name, $found)
    {
        $record->getResult()->mergeAlias($found[0], $found[1], $found[2]);
    }
    /**
     * aliasを適用する
     */
    public function result_mergeAlias($result, $alias_col_name, $src_col_name, $alias)
    {
        if ( ! $alias["type"] && $alias["enum"]) $alias["type"] = "enum";
        $alias["src_col_name"] = $src_col_name;
        $alias["alias_col_name"] = $alias_col_name;
        // $method_name = "retreive_alias".str_camelize($alias["type"]);
        // if ( ! method_exists($this, $method_name)) {
        //     report_error("aliasに対応する処理がありません",array(
        //         "table"=>$this, "alias"=>$alias, "method_name"=>$method_name,
        //     ));
        // }
        if ($src_col_name==="*") {
            // 値を引数に呼び出し f({i=>v1})=>{v1=>v2}
            $dest_values = self::mapReduce($result, $alias);
            // 結果を統合する
            foreach ($result as $key=>$record) {
                $record[$alias_col_name] = $dest_values[$key];
            }
        } else {
            // 値を引数に呼び出し f({i=>v1})=>{v1=>v2}
            $src_values = $result->getHashedBy($src_col_name);
            $dest_values = self::mapReduce($src_values, $alias);
            // 結果を統合する
            foreach ($result as $record) {
                $key = self::encodeKey($record->getColValue($src_col_name));
                $record[$alias_col_name] = $dest_values[$key];
            }
        }
    }
    protected static function mapReduce ($src_values, $alias)
    {
        // checklistのように対象の値が複数となっている
        if ($alias["array"] || $alias["glue"]) {
            $reduced = array_reduce($src_values, function($reduced, $src_value){
                return array_merge($reduced, array_values((array)$src_value));
            }, array());
            // $map = call_user_func($callback, $reduced, $alias);
            $map = app("table.features")->call("alias", $alias["type"], array($reduced, $alias));
            $dest_values = array();
            foreach ($src_values as $src_value) {
                $key = self::encodeKey($src_value);
                $dest_values[$key] = array();
                foreach ((array)$src_value as $k=>$v) $dest_values[$key][$k] = $map[$v];
                if ($alias["glue"]) $dest_values[$key] = implode($glue, $dest_values[$key]);
            }
            return $dest_values;
        } else {
            // $dest_values = call_user_func($callback, $src_values, $alias);
            $dest_values = app("table.features")->call("alias", $alias["type"], array($src_values, $alias));
            return $dest_values;
        }
    }
    protected static function encodeKey($key)
    {
        return (is_array($key) || is_object($key)) ? json_encode($key) : "".$key;
    }
    /**
     * aliasにenum指定がある場合の処理
     */
    public function alias_enum ($src_values, $alias)
    {
        // 指定が不正
        if ( ! $alias["enum"] || ! app()->enum[$alias["enum"]]) {
            report_error("aliasで指定されるenumがありません",array(
                "enum"=>$alias["enum"], "table"=>$this, "alias"=>$alias,
            ));
        }
        return app()->enum[$alias["enum"]]->map($src_values);
    }
    /**
     * hasMany関係先テーブルの情報を1件のみ取得
     */
    public function alias_hasOne ($src_values, $alias)
    {
        $alias["single"] = true;
        return $this->alias_hasMany($src_values, $alias);
    }
    /**
     * hasMany関係先テーブルの情報を取得
     */
    public function alias_hasMany ($src_values, $alias)
    {
        if ( ! $alias["table"]) {
            report_error("aliasで指定されるtableがありません",array(
                "table"=>$this, "assoc_table"=>$alias["table"], "alias"=>$alias
            ));
        }
        $assoc_table = app()->tables[$alias["table"]];
        $assoc_fkey = $alias["fkey"]
            ?: $assoc_table->getColNameByAttr("fkey_for", $assoc_table->getAppTableName());
        if ( ! $assoc_fkey) {
            report_error("Table間にHasMany関係がありません",array(
                "table"=>$this, "assoc_table"=>$assoc_table, "alias"=>$alias,
            ));
        }
        $assoc_table->findBy($assoc_fkey, $src_values);
        if ($alias["mine"]) $assoc_table->findMine();
        if ($alias["where"]) $assoc_table->findBy($alias["where"]);
        if ($alias["order"]) $assoc_table->orderBy($alias["order"]);
        // if ($alias["limit"]) $assoc_table->limit($alias["limit"]);
        if ($alias["summary"]) return $assoc_table->selectSummary($alias["summary"], $assoc_fkey);
        $result = $assoc_table->select();
        if ($alias["single"]) return $result->getMappedBy($assoc_fkey);
        return $result->getGroupedBy($assoc_fkey);
    }
    /**
     * hasMany関係先テーブルの情報をLIMIT付きで取得する
     * 最新n件を取得する、など件数分のSQL発行が必要な場合の処理
     */
    public function alias_hasManyEach ($src_values, $alias)
    {
        if ( ! $alias["table"]) {
            report_error("aliasで指定されるtableがありません",array(
                "table"=>$this, "assoc_table"=>$alias["table"], "alias"=>$alias
            ));
        }
        $values = array();
        foreach ($src_values as $src_value) {
            $assoc_table = app()->tables[$alias["table"]];
            $assoc_fkey = $alias["fkey"]
                ?: $assoc_table->getColNameByAttr("fkey_for", $assoc_table->getAppTableName());
            if ( ! $assoc_fkey) {
                report_error("Table間にHasMany関係がありません",array(
                    "table"=>$this, "assoc_table"=>$assoc_table, "alias"=>$alias,
                ));
            }
            $assoc_table->findBy($assoc_fkey, $src_value);
            if ($alias["mine"]) $assoc_table->findMine();
            if ($alias["where"]) $assoc_table->findBy($alias["where"]);
            if ($alias["order"]) $assoc_table->orderBy($alias["order"]);
            if ($alias["summary"]) return $assoc_table->selectSummary($alias["summary"], $assoc_fkey);
            if ($alias["limit"]) $assoc_table->limit($alias["limit"]);
            if ($alias["single"]) $values[$src_value] = $assoc_table->selectOne();
            else $values[$src_value] = $assoc_table->select();
        }
        return $values;
    }
    /**
     * belongsTo関係先テーブルの情報を取得
     */
    public function alias_belongsTo ($src_values, $alias)
    {
        if ( ! $alias["table"]) {
            report_error("aliasで指定されるtableがありません",array(
                "table"=>$this, "assoc_table"=>$alias["table"], "alias"=>$alias
            ));
        }
        $assoc_table = $this->releasable(table($alias["table"]));
        $assoc_table->findBy($assoc_table->getIdColName(), $src_values);
        if ($alias["mine"]) $assoc_table->findMine();
        if ($alias["where"]) $assoc_table->findBy($alias["where"]);
        return $assoc_table->select()->getMappedBy($assoc_table->getIdColName());
    }
    /**
     * alias type=summaryの処理 集計結果を対応づける
     *      - required table, key, value
     *      - optional joins, where, key_sub
     */
    public function alias_summary ($src_values, $alias)
    {
        $q = $this->releasable(table($alias["table"]));
        $q->findBy($alias["key"], $src_values);
        foreach ((array)$alias["joins"] as $join) $q->join($join);
        if ($alias["where"]) $q->findBy($alias["where"]);
        return $q->selectSummary($alias["value"], $alias["key"], $alias["key_sub"] ?: false);
    }
}
