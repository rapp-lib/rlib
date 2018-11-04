<?php
namespace R\Lib\Table\Plugin;

class StdAliasProvider extends BasePluginProvider
{
    /**
     * @hook on_getBlankCol
     * retreiveメソッドが定義されていたら参照する
     */
    protected function on_getBlankCol_retreive ($record, $col_name)
    {
        // メソッドの探索
        $method_name = "retreive_col".str_camelize($col_name);
        if ( ! method_exists($this, $method_name)) return false;
        // resultを引数に呼び出し
        $values = call_user_func(array($this,$method_name), $record->getResult());
        foreach ($record->getResult() as $key=>$a_record) $a_record[$col_name] = $values[$key];
        return true;
    }
    /**
     * @hook on_getBlankCol
     * aliasが定義されていたら参照する
     */
    protected function on_getBlankCol_alias ($record, $col_name)
    {
        $found = false;
        foreach ((array)static::$aliases as $src_col_name=>$aliases) {
            foreach ((array)$aliases as $alias_col_name=>$alias) {
                if ($alias_col_name===$col_name) {
                    if ($found) {
                        report_error("同名のaliasが重複して登録されています", array(
                            "table"=>$this->getAppTableName(),
                            "alias_col_name"=>$alias_col_name,
                            "src_col_name_1"=>$found[1],
                            "src_col_name_2"=>$src_col_name,
                        ));
                    }
                    $found = array($alias_col_name, $src_col_name, $alias);
                }
            }
        }
        if ( ! $found) return false;
        $this->mergeAlias($record->getResult(), $found[0], $found[1], $found[2]);
        return true;
    }
    /**
     * @hook result
     * aliasを適用する
     */
    protected function mergeAlias ($result, $alias_col_name, $src_col_name, $alias)
    {
        if ( ! $alias["type"] && $alias["enum"]) $alias["type"] = "enum";
        $alias["src_col_name"] = $src_col_name;
        $alias["alias_col_name"] = $alias_col_name;
        $method_name = "retreive_alias".str_camelize($alias["type"]);
        if ( ! method_exists($this, $method_name)) {
            report_error("aliasに対応する処理がありません",array(
                "table"=>$this, "alias"=>$alias, "method_name"=>$method_name,
            ));
        }
        if ($src_col_name==="*") {
            // 値を引数に呼び出し f({i=>v1})=>{v1=>v2}
            $dest_values = self::mapReduce(array($this, $method_name), $result, $alias);
            // 結果を統合する
            foreach ($result as $key=>$record) {
                $record[$alias_col_name] = $dest_values[$key];
            }
        } else {
            // 値を引数に呼び出し f({i=>v1})=>{v1=>v2}
            $src_values = $result->getHashedBy($src_col_name);
            $dest_values = self::mapReduce(array($this, $method_name), $src_values, $alias);
            // 結果を統合する
            foreach ($result as $record) {
                $key = self::encodeKey($record->getColValue($src_col_name));
                $record[$alias_col_name] = $dest_values[$key];
            }
        }

        app("events")->fire("table.merge_alias", array($this, $this->statement,
            $result, $src_col_name, $alias_col_name, $dest_values));
    }
    protected static function mapReduce ($callback, $src_values, $alias)
    {
        // checklistのように対象の値が複数となっている
        if ($alias["array"] || $alias["glue"]) {
            $reduced = array_reduce($src_values, function($reduced, $src_value){
                return array_merge($reduced, array_values((array)$src_value));
            }, array());
            $map = call_user_func($callback, $reduced, $alias);
            $dest_values = array();
            foreach ($src_values as $src_value) {
                $key = self::encodeKey($src_value);
                $dest_values[$key] = array();
                foreach ((array)$src_value as $k=>$v) $dest_values[$key][$k] = $map[$v];
                if ($alias["glue"]) $dest_values[$key] = implode($glue, $dest_values[$key]);
            }
            return $dest_values;
        } else {
            $dest_values = call_user_func($callback, $src_values, $alias);
            return $dest_values;
        }
    }
    protected static function encodeKey($key)
    {
        return (is_array($key) || is_object($key)) ? json_encode($key) : "".$key;
    }
    /**
     * @hook retreive_alias
     * aliasにenum指定がある場合の処理
     */
    protected function retreive_aliasEnum ($src_values, $alias)
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
     * @hook retreive_alias
     * hasMany関係先テーブルの情報を1件のみ取得
     */
    protected function retreive_aliasHasOne ($src_values, $alias)
    {
        $alias["single"] = true;
        return $this->retreive_aliasHasMany($src_values, $alias);
    }
    /**
     * @hook retreive_alias
     * hasMany関係先テーブルの情報を取得
     */
    protected function retreive_aliasHasMany ($src_values, $alias)
    {
        if ( ! $alias["table"]) {
            report_error("aliasで指定されるtableがありません",array(
                "table"=>$this, "assoc_table"=>$alias["table"], "alias"=>$alias
            ));
        }
        $assoc_table = $this->releasable(table($alias["table"]));
        $assoc_fkey = $alias["fkey"]
            ?: $assoc_table->getColNameByAttr("fkey_for", $this->getAppTableName());
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
     * @hook retreive_alias
     * hasMany関係先テーブルの情報をLIMIT付きで取得する
     */
    protected function retreive_aliasHasManyEach ($src_values, $alias)
    {
        if ( ! $alias["table"]) {
            report_error("aliasで指定されるtableがありません",array(
                "table"=>$this, "assoc_table"=>$alias["table"], "alias"=>$alias
            ));
        }
        $values = array();
        foreach ($src_values as $src_value) {
            $assoc_table = $this->releasable(table($alias["table"]));
            $assoc_fkey = $alias["fkey"]
                ?: $assoc_table->getColNameByAttr("fkey_for", $this->getAppTableName());
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
     * @hook retreive_alias
     * belongsTo関係先テーブルの情報を取得
     */
    protected function retreive_aliasBelongsTo ($src_values, $alias)
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
     * @hook retreive_alias
     * alias type=summaryの処理 集計結果を対応づける
     *      - required table, key, value
     *      - optional joins, where, key_sub
     */
    public function retreive_aliasSummary ($src_values, $alias)
    {
        $q = $this->releasable(table($alias["table"]));
        $q->findBy($alias["key"], $src_values);
        foreach ((array)$alias["joins"] as $join) $q->join($join);
        if ($alias["where"]) $q->findBy($alias["where"]);
        return $q->selectSummary($alias["value"], $alias["key"], $alias["key_sub"] ?: false);
    }
}
