<?php
namespace R\Lib\DBI;

use ConnectionManager;
use Model;

/**
 *
 */
class DBI_Base {

    protected $name ="";
    protected $config =array();
    protected $driver_name ="";
    protected $ds =null;
    protected $transaction_stack =array();
    private $desc_cache =array();
    private $dbname = "";

    protected $result_listener = null;

    //-------------------------------------
    // 結果収集オブジェクトの設定
    public function set_result_listener ($result_listener) {

        $this->result_listener =$result_listener;
    }

    //-------------------------------------
    // 初期化
    public function __construct ($name) {

        $this->name =$name;
    }

    //-------------------------------------
    // DB接続
    public function connect ($connect_info)
    {
        static $cake_loader;
        if ( ! $cake_loader) {
            require_once constant("R_LIB_ROOT_DIR")."/assets/dbi/cake2/rlib_cake2.php";
            require_once constant("CAKE_DIR").'/Model/ConnectionManager.php';
        }
        if ($connect_info["driver"] && ! $connect_info["datasource"]) {
            $connect_info["datasource"] ='Database/'.str_camelize($connect_info["driver"]);
        }
        ConnectionManager::create($this->name, $connect_info);
        $this->ds =ConnectionManager::getDataSource($this->name);
        $this->driver_name = $connect_info["driver"];
        $this->dbname = $connect_info["database"];

        $this->config = $connect_info;
        if ( ! isset($this->config["fetch_col_name_include_table"])) {
            $this->config["fetch_col_name_include_table"] = false;
        }
        if ( ! isset($this->config["statement_default_join_type"])) {
            $this->config["statement_default_join_type"] = "LEFT";
        }
    }

    //-------------------------------------
    // Datasourceの取得
    public function get_datasource () {

        return $this->ds;
    }

    //-------------------------------------
    // DB名の取得
    public function get_dbname () {

        return $this->dbname;
    }

    //-------------------------------------
    // Datasourceの実装の読み込み
    public function __call ($func ,$args) {

        return call_user_func_array(array($this->ds,$func),$args);
    }

    //-------------------------------------
    // 最後のSQLエラーを取得
    public function get_last_error () {

        return $this->ds->lastError();
    }

    //-------------------------------------
    // テーブル一覧を取得
    public function desc_tables () {

        if ( ! $this->desc_cache["__TABLES__"]) {

            $this->desc_cache["__TABLES__"] =$this->ds->listSources();
        }

        return $this->desc_cache["__TABLES__"];
    }

    //-------------------------------------
    // テーブル構造解析
    public function desc ($table_name) {

        if ( ! $this->desc_cache[$table_name]) {

            $this->desc_cache[$table_name] =$this->ds->describe($table_name);
        }

        return $this->desc_cache[$table_name];
    }

    //-------------------------------------
    // LAST_INSERT_IDの取得
    public function last_insert_id ($table_name=null, $pkey_name=null) {

        return $this->ds->lastInsertId($table_name,$pkey_name);
    }

    //-------------------------------------
    // NumRowsの取得
    public function last_num_rows () {

        return $this->ds->lastNumRows();
    }

    //-------------------------------------
    // Affectedの取得
    public function last_affected () {

        return $this->ds->lastAffected();
    }

    //-------------------------------------
    // SQL実行
    public function exec (
            $st,
            $report_context=array()) {

        $start_time =microtime(true);

        try {
            $result =$this->ds->execute($st);
        } catch (\PDOException $e) {
            $this->ds->error = implode(' ',$e->errorInfo);
        }

        $elapsed =round((microtime(true) - $start_time)*1000,2)."ms";

        // SQL文の調査
        if ($this->check_driver("is_support_analyze_sql")
                && app()->debug() && ! $this->ds->error) {

            $explain =$this->analyze_sql($st,$elapsed);

            if ($explain["msg"]) {

                $report_context["Explain"] =$explain["msg"];
            }
        }

        report('SQL Exec',array_merge($report_context,array(
            "Statement" =>$st,
            "Elapsed" =>$elapsed,
        )));

        if ($explain["warn"]) {

            foreach ($explain["warn"] as $warn) {

                report('Bad SQL '.$warn,array(
                    "Statement" =>$st,
                    "Full Explain" =>$explain["full"],
                ));
            }
        }

        if ($this->ds->error) {
            // トランザクション起動中であればRollbackする
            if ($this->transaction_stack) {
                $this->rollback();
            }

            report_error('SQL Error',array(
                "Statement" =>$st,
                "Error" =>$this->ds->error,
            ));
        }

        return $result;
    }

    //-------------------------------------
    // Select結果を1件ロード
    public function fetch ($result_source) {

        $this->ds->_result =$result_source;

        // 結果セットが無効であればnullを返す
        if ( ! $this->ds->hasResult()) {

            return null;
        }

        $this->ds->resultSet($result_source);
        $result =$this->ds->fetchResult();

        // データがなければnullを返す
        if ( ! $result) {

            return null;
        }

        $result_copy =array();

        // 階層構造の変更（ $a[Alias][Key] => $a[Key]）

        // [Deprecated] $a[Alias][Key] => $a[Alias.Key]
        $deprecated_flg =$this->config["fetch_col_name_include_table"];

        foreach ((array)$result as $k1 => $v1) {

            foreach ((array)$v1 as $k2 => $v2) {

                $key =$k2;

                if ($deprecated_flg) {
                    $key =is_numeric($k1) ? $k2 : $k1.".".$k2;
                }

                $result_copy[$key] =& $result[$k1][$k2];
            }
        }

        return $result_copy;
    }

    //-------------------------------------
    // Select結果を全件ロード
    public function fetch_all ($result_source) {

        $result =array();

        while ($row =$this->fetch($result_source)) {

            $result[] =$row;
        }

        return $result;
    }

    //-------------------------------------
    // トランザクションのBegin
    public function begin ($transaction_id="default") {

        if ( ! $this->transaction_stack) {

            // [Deprecated] cake1互換処理
            if (isset($this->ds->_commands)) {

                return $this->exec($this->ds->_commands['begin']);

            } else {

                $this->ds->begin();
            }
        }

        array_push($this->transaction_stack,$transaction_id);
    }

    //-------------------------------------
    // トランザクションのCommit
    public function commit ($transaction_id="default") {

        if ( ! $this->transaction_stack) {

            report_warning("Transaction has rollbacked, not commit");
        }

        $target_transaction_id =array_pop($this->transaction_stack);

        if ($transaction_id != $target_transaction_id) {

            report_error("Nested Transaction commit  error",array(
                "target_transaction" =>$transaction_id,
                "missing_transaction" =>$target_transaction_id,
            ));
        }

        if ( ! $this->transaction_stack) {

            // [Deprecated] cake1互換処理
            if (isset($this->ds->_commands)) {

                $this->exec($this->ds->_commands['commit']);

            } else {

                $this->ds->commit();
            }
        }
    }

    //-------------------------------------
    // トランザクションのRollback
    public function rollback () {

        if ( ! $this->transaction_stack) {

            return false;
        }

        // [Deprecated] cake1互換処理
        if (isset($this->ds->_commands)) {

            return $this->exec($this->ds->_commands['rollback']);

        } else {

            $this->ds->rollback();
        }

        $this->transaction_stack =array();

        return true;
    }

    //-------------------------------------
    // Query実行(取得は行わない)
    public function select_nofetch ($query) {

        $st =$this->st_select($query);
        $result =$this->exec($st,array(
            "Type" =>"select_nofetch",
            "Query" =>$query,
        ));
        return $result;
    }

    //-------------------------------------
    // Query実行(全件取得)
    public function select ($query) {

        $st =$this->st_select($query);
        $result =$this->exec($st,array(
            "Type" =>"select",
            "Query" =>$query,
        ));
        $ts =$this->fetch_all($result);

        return $ts;
    }

    //-------------------------------------
    // Query実行(1件のデータ取得)
    public function select_one ($query) {

        $st =$this->st_select($query);
        $result =$this->exec($st,array(
            "Type" =>"select_one",
            "Query" =>$query,
        ));
        $t =$this->fetch($result);

        return $t;
    }

    //-------------------------------------
    // Query実行(件数取得)
    public function select_count ($query) {

        $query["fields"] =array("COUNT(*) AS count");

        unset($query["limit"]);
        unset($query["offset"]);
        unset($query["order"]);

        $st =$this->st_select($query);
        $result =$this->exec($st,array(
            "Type" =>"select_count",
            "Query" =>$query,
        ));
        $t =$this->fetch($result);
        $count =(int)$t["count"];

        return $count;
    }

    //-------------------------------------
    // Query実行(Pager取得)
    public function select_pager ($query) {

        $query["fields"] =array("COUNT(*) AS count");
        $offset =$query["offset"];
        $limit =$query["limit"];
        $paging_slider =$query["paging_slider"]
                ? $query["paging_slider"]
                : 10 ;

        unset($query["paging_slider"]);
        unset($query["limit"]);
        unset($query["offset"]);
        unset($query["order"]);

        if ( ! $limit) {

            return null;
        }

        $count =0;

        // GROUP BY指定がある場合はCOUNT結果を再集計
        if ($query["group"]) {

            $st =$this->st_select($query);
            $result =$this->exec($st,array(
                "Type" =>"select_pager",
                "Query" =>$query,
            ));
            $ts =$this->fetch_all($result);
            $count =count($ts);

        // 件数を集計
        } else {

            $st =$this->st_select($query);
            $result =$this->exec($st,array(
                "Type" =>"select_pager",
                "Query" =>$query,
            ));
            $t =$this->fetch($result);
            $count =(int)$t["count"];
        }

        $pager =$this->build_pager($offset,$limit,$count,$paging_slider);

        return $pager;
    }

    //-------------------------------------
    // Query実行(INSERT)
    public function insert ($query) {

        $st =$this->st_insert($query);
        $result =$this->exec($st,array(
            "Type" =>"insert",
            "Query" =>$query,
        ));

        return $result;
    }

    //-------------------------------------
    // Query実行(UPDATE)
    public function update ($query) {

        $st =$this->st_update($query);

        $result =$this->exec($st,array(
            "Type" =>"update",
            "Query" =>$query,
        ));

        return $result;
    }

    //-------------------------------------
    // Query実行(DELETE)
    public function delete ($query) {

        $st =$this->st_delete($query);

        $result =$this->exec($st,array(
            "Type" =>"delete",
            "Query" =>$query,
        ));

        return $result;
    }

    //-------------------------------------
    // SQL組み立て（JOIN句）
    public function st_joins ($joins) {

        if (is_array($joins) && $joins) {

            foreach ($joins as $k => $v) {

                if (is_array($v)) {

                    // joins.N:(table,conditions,type)構造の展開
                    if (isset($v[0])) {

                        $joins[$k]["table"] =$v[0];
                        unset($joins[$k][0]);

                        if (isset($v[1])) {

                            $joins[$k]["conditions"] =$v[1];
                            unset($joins[$k][1]);
                        }

                        if (isset($v[2])) {

                            $joins[$k]["type"] =$v[2];
                            unset($joins[$k][2]);
                        }
                    }

                    // table:(table,alias)構造の展開
                    if (is_array($joins[$k]["table"])
                            && $joins[$k]["table"][0]) {

                        list(
                            $joins[$k]["table"],
                            $joins[$k]["alias"]
                        ) =$joins[$k]["table"];
                    }

                    // サブクエリの解決
                    if (is_array($joins[$k]["table"])) {

                        $joins[$k]["table"]
                                ='('.$this->st_select($joins[$k]["table"]).')';
                    }

                    // 標準JOIN方式をINNERからLEFTに変更
                    if ( ! isset($joins[$k]["type"])
                            && $this->config["statement_default_join_type"]) {

                        $joins[$k]["type"] =$this->config["statement_default_join_type"];
                    }

                    $joins[$k] =$this->ds->buildJoinStatement($joins[$k]);
                }
            }
        }

        return $joins;
    }

    //-------------------------------------
    // SQL組み立て（SELECT）
    public function st_select ($query) {

        $default_query =array(
            'fields' => array("*"),
            'conditions' => array(),
            'table' => null,
            'alias' => $query["table"],
            'limit' => null,
            'offset' => null,
            'joins' => array(),
            'order' => null,
            'group' => null,
        );
        $query =array_merge($default_query,$query);

        // table:(table,alias)構造の展開
        if (is_array($query["table"]) && $query["table"][0]) {

            list($query["table"],$query["alias"]) =$query["table"];
        }

        // 一部RDBMS全てのfieldsのaliasを"AS TTT__AAA"に設定する
        if ($this->check_driver("is_require_rename_fields")) {

            // aliasの取得
            $aliases =array();
            $alias_name =$query["alias"]
                    ? $query["alias"]
                    : $query["table"];
            $aliases[$alias_name] =array(
                    "table" =>$query["table"],
                    "alias" =>$alias_name);

            foreach ($query["joins"] as $join_query) {

                // joins.N:(table,conditions,type)構造の展開
                if (isset($join_query[0])) {

                    $join_query["table"] =$join_query[0];
                    unset($join_query[0]);
                }

                // table:(table,alias)構造の展開
                if (is_array($join_query["table"])
                        && $join_query["table"][0]) {

                    list(
                        $join_query["table"],
                        $join_query["alias"]
                    ) =$join_query["table"];
                }

                $alias_name =$join_query["alias"]
                        ? $join_query["alias"]
                        : $join_query["table"];
                $aliases[$alias_name] =array(
                        "table" =>$join_query["table"],
                        "alias" =>$alias_name);
            }

            $query["fields"] =$this->rename_fields($query["fields"],$aliases);
        }

        // サブクエリの解決
        if (is_array($query["table"])) {

            $query["table"] ='('.$this->st_select($query["table"]).')';
        }

        // joins
        $query["joins"] =$this->st_joins($query["joins"]);

        // conditions
        $query["conditions"] =$this->ds->conditions($query["conditions"],true,false);

        $model =class_exists("Model") ? new Model() : null;
        $st =$this->ds->buildStatement($query,$model);

        return $st;
    }

    //-------------------------------------
    // SQL組み立て（INSERT）
    public function st_insert ($query) {

        $default_query =array(
            'table' => null,
            'alias' => $query["table"],
            'fields' => array(),
        );
        $query =array_merge($default_query,$query);

        // table:(table,alias)構造の展開
        if (is_array($query["table"])) {

            list($query["table"],$query["alias"]) =$query["table"];
        }

        if ($query["type"]=="insert") {
            $query["type"] = "create";
        }

        // valuesでの指定の展開
        if ($query["values"]) {
            $query["fields"] = $query["values"];
            unset($query["values"]);
        }

        // fields, values
        $insert_fields =array();
        $insert_values =array();

        foreach ((array)$query["fields"] as $k => $v) {

            if (strpos($k,".") !== false) {

                if (strlen($query["alias"])
                        && preg_match('!^'.$query["alias"].'\.([^\.]+)$!',$k,$match)) {

                    $k =$match[1];

                } else {

                    continue;
                }
            }

            if (is_array($v)) {

                $v =serialize($v);
            }

            $insert_fields[] =$this->ds->name($k);
            $insert_values[] =$this->ds->value($v, "string", false);
        }

        $query["fields"] =implode(", ",$insert_fields);
        $query["values"] =implode(", ",$insert_values);

        $st =$this->ds->renderStatement('create',$query);

        return $st;
    }

    //-------------------------------------
    // SQL組み立て（UPDATE）
    public function st_update ($query) {

        $default_query =array(
            'fields' => array(),
            'conditions' => array(),
            'table' => null,
            'joins' => null,
        );
        $query =array_merge($default_query,$query);

        // table:(table,alias)構造の展開
        if (is_array($query["table"])) {

            list($query["table"],$query["alias"]) =$query["table"];
        }

        // alias
        if ( ! $query["alias"]) {

            $query["alias"] =$query["table"];
        }

        // joins
        $query["joins"] =$this->st_joins($query["joins"]);
        $query["joins"] =implode(' ',(array)$query["joins"]);

        // valuesでの指定の展開
        if ($query["values"]) {
            $query["fields"] = $query["values"];
        }

        // fields
        $update_fields =array();

        foreach ($query["fields"] as $k => $v) {

            // Postgres等でfieldsのaliasを削除
            if ($this->check_driver("is_require_drop_field_alias")
                    && preg_match('!^(?:[^\.]+\.)([^\.]+)$!',$k,$match)) {

                $k =$match[1];
            }

            if ($v === null) {

                $update_fields[] =$this->ds->name($k)." = NULL";

            } elseif (is_numeric($k)) {

                $update_fields[] =$v;

            } elseif (is_array($v)) {

                $update_fields[] =$this->ds->name($k)
                        ." = ".$this->ds->value(serialize($v), "string", false);

            } else {

                $update_fields[] =$this->ds->name($k)
                        ." = ".$this->ds->value($v, "string", false);
            }
        }

        $query["fields"] =implode(", ",$update_fields);

        // conditions
        $query["conditions"] =$this->ds->conditions($query["conditions"]);

        $st =$this->ds->renderStatement('update',$query);

        return $st;
    }

    //-------------------------------------
    // SQL組み立て（DELETE）
    public function st_delete ($query) {

        $default_query =array(
            'alias' => null,
            'joins' => null,
            'table' => null,
            'conditions' => array(),
        );
        $query =array_merge($default_query,$query);

        // table:(table,alias)構造の展開
        if (is_array($query["table"])) {

            list($query["table"],$query["alias"]) =$query["table"];
        }

        // alias
        if ( ! $query["alias"]) {

            $query["alias"] =$query["table"];
        }

        // joins
        $query["joins"] =$this->st_joins($query["joins"]);
        $query["joins"] =implode(' ',(array)$query["joins"]);

        // conditions
        $query["conditions"] =$this->ds->conditions($query["conditions"]);

        $st =$this->ds->renderStatement('delete',$query);

        return $st;
    }

    //-------------------------------------
    // SQL組み立て（CREATE TABLE）
    public function st_init_schema ($query) {

        $table_def =$query["table_def"];

        $table =$table_def["table"];
        $columns = $indexes = $tableParameters = array();

        // column設定
        foreach ((array)$table_def["cols"] as $col) {

            $columns[] =$this->ds->buildColumn($col);
        }

        // indexes設定
        if ($table_def["indexes"]) {

            $col =$table_def["indexes"];
            $indexes =array_merge($indexes, $this->ds->buildIndex($col, $table));
        }

        // pkey-index設定
        if ($table_def["pkey"]) {

            $col =array('PRIMARY' =>array('column' =>$table_def["pkey"], 'unique' => 1));
            $indexes =array_merge($indexes, $this->ds->buildIndex($col, $table));
        }

        // tableParameters設定
        if ($table_def["tableParameters"]) {

            $col =$table_def["tableParameters"];
            $tableParameters =$this->ds->buildTableParameters($col, $table);
        }

        $st =$this->ds->renderStatement('schema',
                compact('table', 'columns', 'indexes', 'tableParameters'));

        return $st;
    }

    //-------------------------------------
    // pagerの作成
    public function build_pager ($offset, $length, $total ,$slider=10) {

        $pager =array();

        $total_dup =$total ? $total : 1;

        // 総ページ数
        $pages =ceil($total_dup/$length);
        $pager['numpages'] =$pages;

        // 最初のページと最後のページ
        $pager['firstpage'] =1;
        $pager['lastpage'] =$pages;

        // ページ配列の作成
        $pager['pages'] = array();

        for ($i=1; $i <= $pages; $i++) {

            $coffset = $length * ($i-1);

            $pager['pages'][$i] =$coffset;

            if ($coffset == $offset) {

                $pager['current'] = $i;
            }
        }

        if( ! isset($pager['current'])) {

            $pager['current'] =0;
        }

        // ページ長
        if ($maxpages) {

            $radio = floor($maxpages/2);
            $minpage = $pager['current'] - $radio;

            if ($minpage < 1) {

                $minpage = 1;
            }

            $maxpage = $pager['current'] + $radio - 1;

            if ($maxpage > $pager['numpages']) {

                $maxpage = $pager['numpages'];
            }

            $pager['maxpages'] = $maxpages;

        } else {

            $pager['maxpages'] = null;
        }

        // 前ページ
        $prev = $offset - $length;
        $pager['prev'] = ($prev >= 0) ? $prev : null;

        // 次ページ
        $next = $offset + $length;
        $pager['next'] = ($next < $total) ? $next : null;

        // 残りのページ数
        if ($pager['current'] == $pages) {

            $pager['remain'] = 0;
            $pager['to'] = $total;

        } else {

            if ($pager['current'] == ($pages - 1)) {

                $pager['remain'] = $total - ($length*($pages-1));

            } else {

                $pager['remain'] = $length;
            }

            $pager['to'] = $pager['current'] * $length;
        }

        $pager['from'] = (($pager['current']-1) * $length)+1;
        $pager['total'] =$total;
        $pager['offset'] =$offset + 1;
        $pager['length'] =$length;

        // スライダーの構築
        if ($slider) {

            $pager =$this->build_slider($pager,$slider);
        }

        return $pager;
    }

    //-------------------------------------
    // スライダーの構築
    public function build_slider ($pager ,$slider){

        $pager['pages_slider'] =array();

        $start =1;
        $prev_set =null;
        $next_set =$slider+1;
        $current =$pager['current'];
        $pages_count =count($pager['pages']);

        if ($current+ceil($slider/2) >= $pages_count
                && $pages_count-$slider>0) {

            $start =$pages_count - $slider + 1;

        } elseif ($current-floor($slider/2) > 0) {

            $start =$current - floor($slider/2);
        }

        for ($i=$start; $i<$start+$slider && $i<=$pages_count; $i++) {

            $pager['pages_slider'][$i] =$pager['pages'][$i];
        }

        $pager['slider_prev'] =isset($pager['pages'][$pager['current']-$slider-1])
                ? $pager['pages'][$pager['current']-$slider-1]
                : null;

        $pager['slider_next'] =isset($pager['pages'][$pager['current']+$slider+1])
                ? $pager['pages'][$pager['current']+$slider+1]
                : null;

        $pager['pages_raw'] =$pager['pages'];
        $pager['pages'] =$pager['pages_slider'];

        return $pager;
    }

    //-------------------------------------
    // Postgres等向けに全てのfieldsのaliasを設定する
    // ※Driver内でmapに対して"__"分割でデータを設定する機能の実装が必要
    private function rename_fields ($raw_fields,$aliases) {

        $fields =array();
        $field_pattern_tfa ='!^\s*(?:([_\w\*]+)\.)?([_\w\*]+)(?:\s+AS\s+([_\w\*]+))?\s*$!i';
        $field_pattern_other ='!^(.*?)\s+AS\s+([_\w\*]+)\s*$!i';

        // aliasに対する実fieldの一覧取得
        foreach ($aliases as $i => $alias) {

            $aliases[$i]["desc"] =$this->desc($alias["table"]);
        }

        // fieldsの変換
        foreach ($raw_fields as $field) {

            $field_error =false;

            // 文字列以外の型
            if ( ! is_string(field)) {

                $fields[] =$field;

            // "TTT.FFF AS AAA"形式の処理
            } elseif (preg_match($field_pattern_tfa,$field,$match)) {

                list(,$_t,$_f,$_a) =$match;

                // TTT.FFF
                if ($_t && $_f && $_t!="*" && $_f!="*") {

                    $fields[] ='"'.$_t.'".'.'"'.$_f
                            .'" AS "'.$_t.'__'.($_a ? $_a : $_f).'"';

                // FFF
                } elseif ( ! $_t && $_f && $_f!="*") {

                    foreach ($aliases as $_rt => $alias) {

                        foreach ($alias["desc"] as $_rf => $_df) {

                            if ($_rf == $_f) {

                                $fields[] ='"'.$_rt.'".'.'"'.$_f
                                        .'" AS "'.$_rt.'__'.($_a ? $_a : $_f).'"';
                                break 2;
                            }
                        }
                    }

                // TTT.*
                } elseif ($_t && $_f && $_t!="*" && $_f=="*") {

                    foreach ($aliases[$_t]["desc"] as $_rf => $_df) {

                        $fields[] ='"'.$_t.'".'.'"'.$_rf.'" AS "'.$_t.'__'.$_rf.'"';
                    }

                // *
                } elseif (( ! $_t || $_t=="*") && $_f && $_f=="*") {

                    foreach ($aliases as $_rt => $alias) {

                        foreach ((array)$alias["desc"] as $_rf => $_df) {

                            $fields[] ='"'.$_rt.'".'.'"'.$_rf.'" AS "'.$_rt.'__'.$_rf.'"';
                        }
                    }
                }

            // "... AS AAA"形式の処理
            } elseif (preg_match($field_pattern_other,$field,$match)) {

                $fields[] =$field;

            } else {

                report_warning('Invalid Query-field.',array(
                    "field" =>$field,
                ));
            }
        }

        return $fields;
    }

    //-------------------------------------
    // DBDumpの生成
    public function create_dump ($filename, $compress=false) {

        $info =$this->ds->config;

        $cmd ="";

        if ($info["driver"] == "postgres") {

            // パスワードは"~/.pgpass"で設定する必要があります
            // 設定は"HOST:PORT:DBNAME:USER:PASS"の形式
            $cmd ="pg_dump -c";
            if ($info["host"]) { $cmd .=" -h ".$info["host"]; }
            if ($info["port"]) { $cmd .=" -P ".$info["port"]; }
            if ($info["login"]) { $cmd .=" -U ".$info["login"]; }
            if ($info["database"]) { $cmd .=" -D ".$info["database"]; }
            $cmd .=' | gzip -c9 > "'.$filename.'"';

        } elseif ($info["driver"] == "mysql" || $info["driver"] == "mysqli") {

            $cmd ="mysqldump";
            if ($info["encoding"]) { $cmd .=" --default-character-set=".$info["encoding"]; }
            if ($info["host"]) { $cmd .=" -h ".$info["host"]; }
            if ($info["port"]) { $cmd .=" -P ".$info["port"]; }
            if ($info["login"]) { $cmd .=" -u".$info["login"]; }
            if ($info["password"]) { $cmd .=" -p".$info["password"]; }
            if ($info["database"]) { $cmd .=" ".$info["database"]; }
            $cmd .=' 1> "'.$filename.'"';

        } else {

            report_error("DB driver ".$info["driver"]." is not support DBI::create_dump");
        }

        exec($cmd,$output,$return_var);
        $result = ! $return_var;

        // 圧縮する
        if ($compress && $result) {

            $cmd_gzip ='gzip -cf "'.$filename.'" > "'.$filename.'"';
            exec($cmd_gzip,$output,$return_var);
            $result = ! $return_var;
        }

        $report_params =array("cmd"=>$cmd);
        $result
                ? report('Dump create successfly',$report_params)
                : report_warning('Dump create failur',$report_params);

        return $result;
    }

    //-------------------------------------
    // DBDumpのリストア
    public function restore_dump ($filename, $compress=false) {

        $info =$this->ds->config;

        // 解凍する
        if ($compress) {

            $cmd_gzip ='gzip -cdf "'.$filename.'" > "'.$filename.'"';
            exec($cmd_gzip,$output,$return_var);
            $result = ! $return_var;
        }

        $cmd ="";

        if ($info["driver"] == "postgres") {

            // パスワードは"~/.pgpass"で設定する必要があります
            // 設定は"HOST:PORT:DBNAME:USER:PASS"の形式
            $cmd ='psql';
            if ($info["host"]) { $cmd .=" -h ".$info["host"]; }
            if ($info["port"]) { $cmd .=" -P ".$info["port"]; }
            if ($info["login"]) { $cmd .=" -U ".$info["login"]; }
            if ($info["database"]) { $cmd .=" ".$info["database"]; }
            $cmd .=' < "'.$filename.'" 2>&1';

        } elseif ($info["driver"] == "mysql" || $info["driver"] == "mysqli") {

            $cmd ='mysql';
            if ($info["encoding"]) { $cmd .=" --default-character-set=".$info["encoding"]; }
            if ($info["host"]) { $cmd .=" -h ".$info["host"]; }
            if ($info["port"]) { $cmd .=" -P ".$info["port"]; }
            if ($info["login"]) { $cmd .=" -u".$info["login"]; }
            if ($info["password"]) { $cmd .=" -p".$info["password"]; }
            if ($info["database"]) { $cmd .=" ".$info["database"]; }
            $cmd .=' < "'.$filename.'" 2>&1';

        } else {

            report_error("DB driver ".$info["driver"]." is not support DBI::restore_dump");
        }

        exec($cmd,$output,$return_var);
        $result = ! $return_var;

        $report_params =array(
                "cmd"=>$cmd,
                "output"=>$output);
        $result
                ? report('Dump restore successfly',$report_params)
                : report_warning('Dump restore failur',$report_params) ;

        return $return_var;
    }

    //-------------------------------------
    // SQL文を評価して問題を指摘
    public function analyze_sql ($st, $elapsed=0) {

        if ( ! preg_match('!^SELECT\s!is',$st)) {

            return null;
        }

        $result =$this->ds->execute("EXPLAIN ".$st);
        $ts =$this->fetch_all($result);

        $explain["full"] =array();
        $explain["msg"] =array();
        $explain["warn"] =array();

        foreach ($ts as $i =>$t) {

            $t["Extra"] =array_map("trim",explode(';',$t["Extra"]));

            $msg =$t["select_type"];

            if ($t["type"]) {

                $msg .=".".$t["type"];
            }

            if ($t["table"]) {

                $msg .=" , Table=".$t["table"];
            }

            if ($t["rows"]) {

                $msg .="(".$t["rows"].")";
            }

            if ($t["key"]) {

                $msg .=" , Index=".$t["key"];
            }

            if ($t["Extra"]) {

                $msg .=" , ".implode(" , ",$t["Extra"])."";
            }

            $explain["msg"][] =$msg;

            $full =$t;
            $full["Extra"] =implode(",",$t["Extra"]);
            $explain["full"][] =$full;

            if ($t["type"] == "index") {

                if ($t["select_type"] != "PRIMARY") {

                    $explain["warn"][] ="[INDEX全件スキャン] ".$msg;
                }
            }

            if ($t["type"] == "ALL") {

                $explain["warn"][] ="[★全件スキャン] ".$msg;
            }

            if ($t["select_type"] == "DEPENDENT SUBQUERY") {

                if ($t["type"] == "ref" || $t["type"] == "eq_ref") {

                    $explain["warn"][] ="[参照相関SQ] ".$msg;

                } elseif ($t["type"] == "unique_subquery") {

                    $explain["warn"][] ="[U-INDEX相関SQ] ".$msg;

                } elseif ($t["type"] == "index_subquery") {

                    $explain["warn"][] ="[INDEX相関SQ] ".$msg;

                } else {

                    $explain["warn"][] ="[★★全件スキャン相関SQ] ".$msg;
                }
            }

            foreach ($t["Extra"] as $extra_msg) {
                /*
                if ($extra_msg == "Using filesort") {

                    $explain["warn"][] ="[INDEXのないソート] ".$msg;
                }
                */
                /*
                if ($extra_msg == "Using temporary") {

                    $explain["warn"][] ="[一時テーブルの生成] ".$msg;
                }
                */

                if ($extra_msg == "Using join buffer") {

                    $explain["warn"][] ="[★★全件スキャンJOIN] ".$msg;
                }
            }
        }

        return $explain;
    }

    //-------------------------------------
    // プレースホルダーへの値の設定
    public function bind_DELETE ($name=null, $value=null) {

        //return array_registry($this->ds->bounds,$name,$value);
    }

    //-------------------------------------
    // ドライバの判定
    protected function check_driver ($mode) {

        if ($mode == "is_support_analyze_sql") {

            return $this->driver_name == "mysql";
        }

        if ($mode == "is_require_rename_fields") {

            return $this->driver_name == "postgres"
                    || $this->driver_name == "sqlite3"
                    || $this->driver_name == "sqlite";
        }

        if ($mode == "is_require_drop_field_alias") {

            return $this->driver_name == "postgres"
                    || $this->driver_name == "sqlite3"
                    || $this->driver_name == "sqlite";
        }

        return false;
    }
}
