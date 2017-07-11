<?php
namespace R\Lib\Table;
use R\Lib\DBAL\Regacy\DBI_Base;

/**
 * Tableクラスのコア機能セット
 */
class Table_Core
{
    /**
     * Queryオブジェクト
     */
    protected $query;

    /**
     * Resultオブジェクト
     */
    protected $result;

    /**
     * テーブルの定義
     */
    protected static $table_name = null;
    protected static $ds_name = "default";
    protected static $def = array();
    protected static $cols = array();

    /**
     * テーブル別のListenerメソッドの定義
     */
    protected static $defined_listener_method = array();

    /**
     * Hook処理の呼び出し履歴
     */
    private $hook_history = array();

    /**
     * DBIのSQL実行結果リソース
     */
    private $result_res = null;

    /**
     * Insert発行直後のLastInsertId
     */
    private $last_insert_id = null;

    /**
     * buildQueryの結果作成されたSQL文
     */
    private $statemenet = false;

    /**
     * fetchが最後まで完了しているかどうか
     */
    private $fetch_done = false;

    /**
     * @override
     */
    public function __construct ()
    {
        $this->query = new Query;
        $this->result = null;

        // テーブル名を関連づける
        if (static::$table_name) {
            $this->query->setDbname($this->getConnection()->getDbname());
            $this->query->setTable(static::$table_name);
        }
    }

    /**
     * @override
     */
    public function __call ($method_name, $args=array())
    {
        // chain_メソッドの呼び出し
        $chain_method_name = "chain_".$method_name;
        if (method_exists($this, $chain_method_name)) {
            call_user_func_array(array($this,$chain_method_name),$args);
            return $this;
        }

        // MEMO: Queryの操作はTableクラス外から行えない
        // 極力Tableクラス内にQuery操作用の抽象メソッドを定義するべき

        report_error("メソッドの定義がありません",array(
            "method_name" => $method_name,
            "chain_method_name" => $chain_method_name,
            "table" => $this,
        ));
    }

// -- DBConnection取得

    protected $connection = null;
    /**
     * dsnameに対応するDBConnectionを取得する
     */
    public function getConnection ()
    {
        return app()->db(static::$ds_name);
    }

// -- SQLBuilder取得

    protected $sql_builder = null;
    /**
     * SQLBuilderを取得する
     */
    public function getSQLBuilder ()
    {
        if ( ! $this->sql_builder) {
            $db = $this->getConnection();
            $this->sql_builder = new QuerySQLBuilder(array(
                "quote_name" => array($db,"quoteName"),
                "quote_value" => array($db,"quoteValue"),
            ));
        }
        return $this->sql_builder;
    }

// -- 関連オブジェクトのFactory

    /**
     * Tableオブジェクトを作成する
     */
    public function createTable ()
    {
        $class_name = get_class($this);
        if ( ! preg_match('!([a-zA-Z0-9]+)Table$!',$class_name,$match)) {
            report_error("Tableクラスの命名が不正です",array(
                "class_name" => $class_name,
            ));
        }
        return table($match[1]);
    }
    /**
     * Recordオブジェクトを作成する
     */
    public function createRecord ($values=null, $id=null)
    {
        $record = new Record($this);
        // 値の設定
        if (isset($values)) {
            foreach ($values as $k => $v) {
                $record[$k] = $v;
            }
        }
        // IDカラムの値の設定
        if (isset($id)) {
            $id_col_name = $this->getIdColName();
            $record[$id_col_name] = $id;
        }
        return $record;
    }

// -- 定義の取得

    /**
     * Table定義の取得
     */
    public function getDef ()
    {
        $table_def = (array)static::$def;
        $table_def["table_name"] = static::$table_name;
        $table_def["ds_name"] = static::$ds_name;
        $table_def["cols"] = (array)static::$cols;
        return $table_def;
    }
    /**
     * Col定義の取得
     */
    public function getColDef ($col_name)
    {
        return static::$cols[$col_name];
    }
    /**
     * ID属性の指定されたカラム名の取得
     */
    protected function getIdColName ()
    {
        $id_col_name = $this->getColNameByAttr("id");
        if ( ! $id_col_name) {
            report_error("idカラムが定義されていません",array(
                "table" => $this,
            ));
        }
        return $id_col_name;
    }
    /**
     * 属性の指定されたカラム名の取得
     */
    protected function getColNameByAttr ($attr, $value=true)
    {
        foreach (static::$cols as $col_name => $col) {
            if (($value===true && $col[$attr]) || $col[$attr]===$value) {
                return $col_name;
            }
        }
        return null;
    }
    /**
     * 属性の指定されたカラム名をすべて取得
     */
    protected function getColNamesByAttr ($attr, $value=true)
    {
        $cols = array();
        foreach (static::$cols as $col_name => $col) {
            if ($col[$attr]===$value) {
                $cols[] = $col_name;
            }
        }
        return $cols;
    }
    /**
     * Query上で参照可能なTable名の取得
     */
    public function getQueryTableName ()
    {
        return $this->query->getTableName();
    }

// -- resultに対するHook

    /**
     * @hook result
     * Pagerの取得
     */
    public function result_getPager ($result)
    {
        // limit指定のないSQLに対するPagerは発行不能
        if ( ! $this->query["limit"]) {
            return null;
        }
        if ( ! isset($this->pager)) {
            // Pager取得用にSQL再発行
            $query = clone($this->query);
            $query["fields"] = array("count"=>"COUNT(*)");
            unset($query["limit"]);
            unset($query["offset"]);
            unset($query["order"]);
            $statement = $this->getSQLBuilder()->render($this->query);
            $result_res = $this->getConnection()->exec($statement);
            $t = $this->getConnection()->fetch($result_res);
            $count = (int)$t["count"];
            // Pager組み立て
            $this->pager = new Pager($count, $this->query["offset"], $this->query["limit"]);
        }
        return $this->pager;
    }
    /**
     * @hook result
     * 各レコードの特定カラムのみの配列を取得する
     */
    public function result_getHashedBy ($result, $col_name, $col_name_sub=false)
    {
        $hashed_result = array();
        foreach ($result as $key => $record) {
            if ($col_name_sub === false) {
                $hashed_result[$key] = $record[$col_name];
            } else {
                $hashed_result[$record[$col_name]] = $record[$col_name_sub];
            }
        }
        return $hashed_result;
    }
    /**
     * @hook result
     * 各レコードを特定カラムでグループ化した配列を取得する
     */
    public function result_getGroupedBy ($result, $col_name, $col_name_sub=false)
    {
        $grouped_result = array();
        foreach ($result as $key => $record) {
            if ($col_name_sub === false) {
                $grouped_result[$record[$col_name]][$key] = $record;
            } else {
                $grouped_result[$record[$col_name][$col_name_sub]][$key] = $record;
            }
        }
        return $grouped_result;
    }
    /**
     * @hook result
     * InsertしたレコードのIDを取得
     * ※値が書き換わるのでINSERT直後に一度呼び出しておくことを推奨
     */
    public function result_getLastInsertId ($result)
    {
        if ( ! isset($this->last_insert_id)) {
            $this->last_insert_id = $this->getConnection()->lastInsertId(static::$table_name, $this->getIdColName());
        }
        return $this->last_insert_id;
    }
    /**
     * @hook result
     * 1結果レコードのFetch
     */
    public function result_fetch ($result)
    {
        // Fetch完了済みであれば処理しない
        if ($this->fetch_done) {
            return false;
        }
        // fetch実処理
        $data = $this->getConnection()->fetch($this->result_res);
        // 結果セットの残りがなければ完了済みとする
        if ( ! $data) {
            // on_fetchEnd_*を呼び出す
            $this->callListenerMethod("fetchEnd");

            $this->fetch_done = true;
            return false;
        }
        // 結果レコードを組み立てて値をHydrateする
        $record = $this->createRecord();
        $record->hydrate($data);

        // マッピング
        $no_mapping = $this->query->getNoMapping();
        $id_col_name = $this->getColNameByAttr("id");
        if ($id_col_name && ! $no_mapping) {
            // ID列を取得していればIDでマッピング
            if (isset($record[$id_col_name])) {
                $result[$record[$id_col_name]] = $record;
            // 指定が無ければ連番でマッピング
            } else {
                $result[] = $record;
            }
        }
        // on_fetch_*を呼び出す
        $this->callListenerMethod("fetch",array($record));
        return $record;
    }
    /**
     * @hook result
     * 全結果レコードのFetch
     */
    public function result_fetchAll ($result)
    {
        while ($result->fetch() !== false);
        return $result;
    }

// -- recordに対するHook

    /**
     * @hook record
     * Hydrate処理
     */
    public function record_hydrate ($record, $data)
    {
        // QueryのFROMとなったテーブル名の確認
        $query_table_name = $this->query->getTableName();
        // QueryのFROMとなったテーブル以外の値は階層を下げてHydrate
        foreach ((array)$data as $table_name => $values) {
            foreach ((array)$values as $col_name => $value) {
                if ($query_table_name == $table_name) {
                    $record[$col_name] = $value;
                } else {
                    $record[$table_name][$col_name] = $value;
                }
            }
        }
    }
    /**
     * @hook record
     * IDの設定によりInsert/Update処理
     */
    public function record_save ($record)
    {
        $values =(array)$record;
        // IDが指定されていれば削除してIDを条件に指定する
        $id_col_name = $this->getIdColName();
        $id = $values[$id_col_name];
        unset($values[$id_col_name]);
        $table = $this->createTable();
        // IDが指定されていればUpdate、指定が無ければInsert
        if (isset($id)) {
            return $table->updateById($id,$values);
        } else {
            $result = $table->insert($values);
            $record[$id_col_name] = $result->getLastInsertId();
            return $result;
        }
    }

// -- SELECT文の発行

    /**
     * SELECT文の発行 全件取得
     */
    public function select ($fields=array())
    {
        $this->query->addFields($fields);
        return $this->execQuery("select")->fetchAll();
    }
    /**
     * SELECT文の発行 Fetch/マッピングを行わない
     */
    public function selectNoFetch ($fields=array())
    {
        $this->query->addFields($fields);
        $this->query->setNoMapping(true);
        return $this->execQuery("select");
    }
    /**
     * SELECT文の発行 1件取得
     */
    public function selectOne ($fields=array())
    {
        $this->query->addFields($fields);
        $this->execQuery("select");
        $record = $this->result->fetch();
        if ($this->result->fetch()) {
            report_warning("複数Record取得して最初の1件のみを結果とします",array(
                "table" => $this,
            ));
        }
        return $record;
    }
    /**
     * idを指定してSELECT文の発行 1件取得
     */
    public function selectById ($id, $fields=array())
    {
        $this->findById($id);
        return $this->selectOne($fields);
    }
    /**
     * SELECT文の発行 全件取得してハッシュを作成
     */
    public function selectHash ($col_name)
    {
        $id_col_name = $this->getIdColName();
        $this->query->addField($id_col_name);
        $this->query->addField($col_name);
        $ts = $this->select();
        return $ts->hashBy($col_name);
    }

// -- INSERT/UPDATE/DELETE文の発行

    /**
     * idの指定の有無によりINSERT/UPDATE文の発行
     */
    public function save ($values=array())
    {
        $this->query->addValues($values);
        // IDが指定されていればUpdate
        $id_col_name = $this->getIdColName();
        if ($id = $this->query->getValue($id_col_name)) {
            $this->query->removeValue($id_col_name);
            return $this->updateById($id);
        // IDの指定が無ければInsert
        } else {
            return $this->insert();
        }
    }
    /**
     * INSERT文の発行
     */
    public function insert ($values=array())
    {
        $this->query->addValues($values);
        return $this->execQuery("insert");
    }
    /**
     * idを指定してUPDATE文の発行
     */
    public function updateById ($id, $values=array())
    {
        $this->findById($id);
        return $this->updateAll($values);
    }
    /**
     * UPDATE文の発行
     */
    public function updateAll ($values=array())
    {
        $this->query->addValues($values);
        return $this->execQuery("update");
    }
    /**
     * DELETE文の発行
     */
    public function deleteById ($id)
    {
        $this->findById($id);
        return $this->deleteAll();
    }
    /**
     * DELETE文の発行
     */
    public function deleteAll ()
    {
        $this->query->setDelete(true);
        return $this->execQuery("update");
    }

// -- トランザクションの操作

    /**
     * BEGINの発行
     */
    public function begin ()
    {
        return $this->getConnection()->begin();
    }
    /**
     * COMMITの発行
     */
    public function commit ()
    {
        return $this->getConnection()->commit();
    }
    /**
     * ROLLBACKの発行
     */
    public function rollback ()
    {
        return $this->getConnection()->rollback();
    }

// -- SQL組み立て/発行実処理

    /**
     * Queryを完成させる
     */
    public function buildQuery ($type=null)
    {
        // 作成済みであればSQL文を返す
        if ($this->statement) {
            return $this->statement;
        }
        // typeの指定を確認する
        if ($type) {
            $this->query->setType($type);
        }
        $type = $this->query->getType();
        if ( ! $type) {
            report_error("組み立てるQueryのtypeが指定されていません",array(
                "table" => $this,
            ));
        }
        // Query組み立て処理を呼び出す
        $this->callBuildQueryMethods();
        // SQL組み立て
        return $this->statement = $this->getSQLBuilder()->render($this->query);
    }
    /**
     * SQLの発行実処理
     */
    public function execQuery ($type=null)
    {
        // 実行済みであれば結果を返す
        if ($this->result) {
            return $this->result;
        }
        // Query組み立ての仕上げ処理
        $statement = $this->buildQuery($type);
        // SQLの実行
        $this->result_res = $this->getConnection()->exec($statement, array("table"=>$this));
        // Resultの組み立て
        $this->result = new Result($this);
        // LastInsertIdの確保
        if ($type=="insert") {
            $this->result->getLastInsertId();
        }
        // on_afterWrite_*を呼び出す
        if ($type=="insert" || $type=="update") {
            $this->callListenerMethod("afterWrite",array($record));
        }

        return $this->result;
    }

// -- hookメソッド呼び出し関連処理

    /**
     * buildQuery上で必要になるon_*_*処理をまとめて呼び出す
     */
    private function callBuildQueryMethods ()
    {
        // 呼び出すHookを選択
        $hooks = array();
        $type = $this->query->getType();
        // type別
        $hooks[] =$type;
        // valuesを持つQuery
        if ($type=="insert" || $type=="update") {
            $hooks[] ="write";
        }
        // whereを持つQuery
        if ($type=="select" || $type=="update") {
            $hooks[] ="read";
        }
        // 全て
        $hooks[] ="any";
        // 呼び出す
        foreach ($hooks as $hook) {
            $this->callListenerMethod($hook,array());
        }
    }
    /**
     * on hookメソッドを呼び出す
     */
    protected function callListenerMethod ($hook, $args=array())
    {
        // 定義されているon_*_*メソッド名を収集
        $defined = & self::$defined_listener_method[get_class($this)];
        if ( ! isset($defined)) {
            $defined = array();
            foreach (get_class_methods($this) as $method_name) {
                if (strpos($method_name,"on_")!==0) {
                    continue;
                }
                $pattern = explode("_",$method_name,3);
                $defined[$pattern[1]][] = $method_name;
            }
        }

        // Hookを呼び出す
        foreach ((array)$defined[$hook] as $method_name) {
            $this->callHookMethod($method_name,$args);
        }
    }
    /**
     * hookメソッドを呼び出す
     */
    protected function callHookMethod ($method_name, $args=array())
    {
        if (method_exists($this, $method_name)) {
            $result = call_user_func_array(array($this,$method_name),$args);
            if ($result!==false) {
                // 履歴への登録
                $this->hook_history[] = $method_name;
                return true;
            }
        }
        //TODO: extentionの探索
        return false;
    }

// -- その他

    /**
     * reportの呼び出し時の処理
     */
    public function __report ()
    {
        return array(
            "query" => $this->query,
            "history" => (array)$this->hook_history,
        );
    }

    /**
     * 完成済みのQueryからSQL文を取得する
     * @deprecated
     */
    private function queryToStatement ($query)
    {
        $query = (array)$query;
        if ($query["type"]=="select" && ! $query["fields"]) {
            // 空のfieldsを*に変換
            $query["fields"] = array("*");
        }
        foreach ((array)$query["fields"] as $k => $v) {
            // Fieldsのサブクエリ展開
            if (is_object($v) && method_exists($v,"buildQuery")) {
                $query["fields"][$k] = $v = "(".$v->buildQuery("select").")";
            }
            // FieldsのAlias展開
            if ( ! is_numeric($k)) {
                $query["fields"][$k] = $v = $v." AS ".$k;
            }
        }
        foreach ((array)$query["joins"] as $k => $v) {
            // Joinsのサブクエリ展開
            if (is_object($v["table"]) && method_exists($v["table"],"buildQuery")) {
                $v["table"]->modifyQuery(function($sub_query) use (&$query, $k){
                    $sub_query_statement = $query["joins"][$k]["table"]->buildQuery("select");
                    if ($sub_query->getGroup()) {
                        //TODO: GroupBy付きのJOINでも異なるDB間でJOINできるようにする
                        $query["joins"][$k]["table"] = "(".$sub_query_statement.")";
                    } else {
                        $table_name = $sub_query->getTableName();
                        // 異なるDB間でのJOIN時にはDBNAME付きのTable名とする
                        if ($query["dbname"]!==$sub_query["dbname"]) {
                            $table_name = $sub_query["dbname"].".".$table_name;
                        }
                        $query["joins"][$k]["table"] = $table_name;
                        $query["joins"][$k]["conditions"][] = $sub_query["conditions"];
                    }
                });
            }
        }
        // Updateを物理削除に切り替え
        if ($query["type"]=="update" && $query["delete"]) {
            unset($query["delete"]);
            $query["type"] = "delete";
        }

        // SQL文の作成
        $st_method = "st_".$query["type"];
        return $this->getDBI()->$st_method($query);
    }
    /**
     * @deprecated
     * DB名を取得する
     */
    protected function getDbname ()
    {
        return $this->getConnection()->getDbname();
    }
    /**
     * @deprecated
     * DBIオブジェクトの取得
     */
    private function getDBI ()
    {
        if ( ! defined("DBI_LOADED")) {
            register_shutdown_function(function(){
                $instance = & $GLOBALS["loaded_dbi"];
                foreach ((array)$instance as $dbi) {
                    $result =$dbi->rollback();
                }
            });
            define("DBI_LOADED",true);
        }
        $instance =& $GLOBALS["loaded_dbi"];
        $name =static::$ds_name;

        if ( ! $instance[$name]) {
            $connect_info =app()->config("db.connection.".$name);
            $instance[$name] =new DBI_Base($name);
            $instance[$name]->connect($connect_info);
        }
        return $instance[$name];
    }
}
