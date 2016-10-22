<?php
namespace R\Lib\Table;

use R\Lib\DBI\Model_Base;
use R\Lib\DBI\DBI_Base;

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
    protected function getColNameByAttr ($attr)
    {
        foreach (static::$cols as $col_name => $col) {
            if ($col[$attr]) {
                return $col_name;
            }
        }
        return null;
    }

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
            $query_array = (array)$this->query;
            $count = $this->getDBI()->select_count($query_array);
            $this->pager = new Pager($result, $count, $this->query["offset"], $this->query["limit"]);
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
                $hashed_result[$key] = $record[$col_name][$col_name_sub];
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
     */
    public function result_getLastInsertId ($result)
    {
        $ds = $this->getDBI()->get_datasource();
        $id_col_name = $this->getIdColName();
        $table_name = static::$table_name;
        return $this->ds->lastInsertId($table_name,$id_col_name);
    }

    /**
     * @hook result
     * Insert/UpdateしたレコードのIDを複数取得
     */
    public function result_getLastSaveIds ($result)
    {
        $id = null;
        $type = $this->query->getType();
        if ($type == "insert") {
            $id = $result->getLastInsertId();
        } elseif ($type == "update") {
            $id_col_name = $this->getIdColName();
            $id = $this->query->getCondition($id_col_name);
        }
        if ( ! $id) {
            report_error("IDが取得できません",array(
                "table" => $this,
            ));
        }
        return is_array($id) ? $id : array($id);
    }

    /**
     * @hook result
     * Insert/UpdateしたレコードのIDを取得
     */
    public function result_getLastSaveId ($result)
    {
        $ids = $result->getLastSaveIds();
        if (count($ids)>1) {
            report_error("条件にIDが複数指定されています",array(
                "table" => $this,
            ));
        }
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

        $ds = $this->getDBI()->get_datasource();
        // 実行結果が無効であれば処理しない
        if ( ! $ds) {
            return false;
        }
        // fetch実処理
        $ds->resultSet($ds->_result = $this->result_res);
        $data =$ds->fetchResult();

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
        if ( ! $no_mapping) {
            // ID列を取得していればIDでマッピング
            $id_col_name = $this->getIdColName();
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

    /**
     * @hook record
     * Hydrate処理
     */
    public function record_hydrate ($record, $data)
    {
        // QueryのFROMとなったテーブル名の確認
        $query_table_name = $this->query->getAlias();
        if ( ! $query_table_name) { $query_table_name = $this->query->getTable(); }
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
        return $id ? $table->updateById($id,$values) : $table->insert($values);
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
     * SELECT文の発行 1件取得
     */
    public function selectOne ($fields=array())
    {
        $this->query->addFields($fields);
        $this->execQuery("select");
        return $this->result->fetch();
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
        $this->query->setNoMapping(true);report($this);
        return $this->execQuery("select");
    }

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

    /**
     * BEGIN文の発行
     */
    public function transactionBegin ()
    {
        return $this->getDBI()->begin();
    }

    /**
     * COMMIT文の発行
     */
    public function transactionCommit ()
    {
        return $this->getDBI()->commit();
    }

    /**
     * ROLLBACK文の発行
     */
    public function transactionRollback ()
    {
        return $this->getDBI()->rollback();
    }

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

        // テーブル名を関連づける
        if ( ! static::$table_name) {
            report_error("Tableが物理定義されていません",array(
                "table" => $this,
            ));
        }
        $this->query->setTable(static::$table_name);

        // Query組み立て処理を呼び出す
        $this->callBuildQueryMethds();

        // SQL組み立て用配列をDBIの仕様にあわせて加工
        $query = (array)$this->query;

        // 空のfieldsを*に変換
        if ($type=="select" && ! $query["fields"]) {
            $query["fields"] = array("*");
        }
        foreach ((array)$query["fields"] as $k => $v) {
            // Fieldsのサブクエリ展開
            if (method_exists($v,"buildQuery")) {
                $query["fields"][$k] = $v = "(".$v->buildQuery("select").")";
            }
            // FieldsのAlias展開
            if ( ! is_numeric($k)) {
                $query["fields"][$k] = $v = $v." AS ".$k;
            }
        }
        foreach ((array)$query["joins"] as $k => $v) {
            // Joinsのサブクエリ展開
            if (method_exists($v["table"],"buildQuery")) {
                $query["joins"][$k]["table"] = $v["table"] = "(".$v["table"]->buildQuery("select").")";
            }
        }

        // Updateを物理削除に切り替え
        if ($type=="update" && $query["delete"]) {
            $type = "delete";
        }

        // SQL文の作成
        $st_method = "st_".$type;
        $this->statement = $this->getDBI()->$st_method($query);
        return $this->statement;
    }

    /**
     * Queryの発行
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
        $this->result_res =$this->getDBI()->exec($statement, array(
            "Table" =>$this,
        ));
        // Resultの組み立て
        $this->result = new Result($this);

        if ($type=="insert" || $type=="update") {
            // on_afterWrite_*を呼び出す
            $this->callListenerMethod("afterWrite",array($record));
        }

        return $this->result;
    }

    /**
     * assoc処理 selectの発行前
     */
    private function on_select_assoc ()
    {
        foreach ((array)$this->query->getFields() as $i => $col_name) {
            if ( ! is_numeric($i)) {
                $col_name = $i;
            }
            if ($assoc = static::$cols[$col_name]["assoc"]) {
                // fields→assoc_fieldsに項目を移動
                $this->query->removeField($col_name);
                $this->query->addAssocField($col_name);
                // assoc処理の呼び出し
                $this->callHookMethod("assoc_select_".$assoc, array($col_name));
            }
        }
        return false;
    }

    /**
     * assoc処理 各レコードfetch後
     */
    private function on_fetch_assoc ($record)
    {
        foreach ((array)$this->query->getAssocFields() as $col_name) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_fetch_".$assoc, array($col_name, $record));
        }
        return false;
    }

    /**
     * assoc処理 fetch完了後
     */
    private function on_fetchEnd_assoc ()
    {
        foreach ((array)$this->query->getAssocFields() as $col_name) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_fetchEnd_".$assoc, array($col_name));
        }
        return false;
    }

    /**
     * assoc処理 insert/updateの発行前
     */
    private function on_write_assoc ()
    {
        foreach ((array)$this->query->getValues() as $col_name => $value) {
            if ($assoc = static::$cols[$col_name]["assoc"]) {
                // values→assoc_valuesに項目を移動
                $this->query->removeValue($col_name);
                $this->query->setAssocValue($col_name,$value);
                // assoc処理の呼び出し
                $this->callHookMethod("assoc_write_".$assoc, array($col_name,$value));
            }
        }
        return false;
    }

    /**
     * assoc処理 insert/updateの発行後
     */
    private function on_afterWrite_assoc ()
    {
        foreach ((array)$this->query->getAssocValues() as $col_name => $value) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_afterWrite_".$assoc, array($col_name,$value));
        }
        return false;
    }

    /**
     * Query組み立て処理を呼び出す
     */
    private function callBuildQueryMethds ()
    {
        // 呼び出すHookを選択
        $hooks = array();
        $suffixes = array("");
        if (auth()->checkAuthenticated()) {
            // 認証時の処理呼び出し
            $suffixes[] = "As".str_camelize(auth()->getAccount()->getRole());
        }
        $type = $this->query->getType();
        foreach ($suffixes as $suffix) {
            // type別
            $hooks[] =$type.$suffix;
            // valuesを持つQuery
            if ($type=="insert" || $type=="update") {
                $hooks[] ="write".$suffix;
            }
            // whereを持つQuery
            if ($type=="select" || $type=="update") {
                $hooks[] ="read".$suffix;
            }
            // 全て
            $hooks[] ="any".$suffix;
        }
        // 呼び出す
        foreach ($hooks as $hook) {
            $this->callListenerMethod($hook,array());
        }
    }

    /**
     * on_*_*メソッドを呼び出す
     */
    private function callListenerMethod ($hook, $args=array())
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
     * Hookメソッドを呼び出す
     */
    private function callHookMethod ($method_name, $args=array())
    {
        if (method_exists($this, $method_name)) {
            $result = call_user_func_array(array($this,$method_name),$args);
            if ($result!==false) {
                // 履歴への登録
                $this->hook_history[] = $method_name;
                return true;
            }
        }
        return false;
    }

    /**
     * @deprecated
     * @override
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
     * @deprecated
     * Modelオブジェクトの取得
     */
    protected function getModel ()
    {
        $instance =& ref_globals("loaded_model");

        if ( ! $instance) {
            $instance = new Model_Base;
        }
        return $instance;
    }

    /**
     * @deprecated
     * DBIオブジェクトの取得
     */
    private function getDBI ()
    {
        if ( ! defined("DBI_LOADED")) {
            register_shutdown_webapp_function(function(){
                $instance = & ref_globals("loaded_dbi");
                foreach ((array)$instance as $dbi) {
                    $result =$dbi->rollback();
                }
            });
            define("DBI_LOADED",true);
        }

        $instance = & ref_globals("loaded_dbi");
        $name =static::$ds_name;

        if ( ! $instance[$name]) {
            $connect_info =registry("DBI.connection.".$name);
            $instance[$name] =new DBI_Base($name);
            $instance[$name]->connect($connect_info);
        }
        return $instance[$name];
    }
}
