<?php
namespace R\Lib\Table;

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
    protected static $aliases = array();
    protected static $fkey_routes = array();
    protected static $rules = array();
    /**
     * クエリ発行時のTransactionの自動Begin/Commit設定
     */
    protected static $auto_begin = true;
    protected static $auto_commit = true;
    /**
     * テーブル別のListenerメソッドの定義
     */
    protected static $defined_listener_method = array();
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
     * Insert発行直後のLastInsertId
     */
    private $last_insert_id = null;
    /**
     * Select条件に対応する登録総数
     */
    private $total = null;
    /**
     * totalに対応するPager
     */
    private $pager = null;
    /**
     * このTable処理内でのTransaction処理状態
     */
    private $in_transaction = null;

    /**
     * @override
     */
    public function __construct ($query=array())
    {
        $this->query = new Query($query);
        // テーブル名を関連づける
        $this->query->setDbname($this->getConnection()->getDbname());
        $this->query->setTable(array($this->getDefTableName(), $this->getAppTableName()));
    }
    /**
     * @override
     */
    public function __call ($method_name, $args=array())
    {
        // chain_メソッドの呼び出し
        $chain_method_name = "chain_".$method_name;
        if (method_exists($this, $chain_method_name)) {
            array_unshift($args, $this->query);
            call_user_func_array(array($this,$chain_method_name),$args);
            return $this;
        }
        report_error("メソッドの定義がありません",array(
            "method_name" => $method_name,
            "chain_method_name" => $chain_method_name,
            "table" => $this,
        ));
    }

// -- DBConnection取得

    /**
     * dsnameに対応するDBConnectionを取得する
     */
    public function getConnection ()
    {
        return app()->db(static::$ds_name);
    }

// -- 関連オブジェクトのFactory

    /**
     * Tableオブジェクトを作成する
     */
    public function createTable ($query=array(), $reult=null)
    {
        $class = get_class($this);
        return new $class($query, $reult);
    }
    /**
     * Resultオブジェクトを作成する
     */
    public function createResult ()
    {
        return new Result($this);
    }
    /**
     * Recordオブジェクトを作成する
     */
    public function result_createRecord ($result, $values=null, $id=null)
    {
        $result[] = $record = new Record($result);
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
    public static function getDef ()
    {
        $table_def = (array)static::$def;
        $table_def["table_name"] = static::$table_name;
        $table_def["ds_name"] = static::$ds_name;
        $table_def["cols"] = (array)static::$cols;
        $table_def["aliases"] = (array)static::$aliases;
        $table_def["fkey_routes"] = (array)static::$fkey_routes;
        $table_def["rules"] = (array)static::$rules;
        return $table_def;
    }
    /**
     * Col定義の取得
     */
    public static function getColDef ($col_name)
    {
        return static::$cols[$col_name];
    }
    /**
     * ID属性の指定されたカラム名の取得
     */
    public static function getIdColName ()
    {
        $id_col_name = static::getColNameByAttr("id");
        if ( ! $id_col_name) {
            report_error("idカラムが定義されていません",array(
                "table" => static::$table_name,
            ));
        }
        return $id_col_name;
    }
    /**
     * 属性の指定されたカラム名の取得
     */
    public static function getColNameByAttr ($attr, $value=true)
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
    public static function getColNamesByAttr ($attr, $value=true)
    {
        $cols = array();
        foreach (static::$cols as $col_name => $col) {
            if (($value===true && $col[$attr]) || $col[$attr]===$value) {
                $cols[] = $col_name;
            }
        }
        return $cols;
    }

// -- Table情報取得

    /**
     * SQL文中で参照可能なTable名の取得
     */
    public function getQueryTableName ()
    {
        return $this->query->getTableName();
    }
    /**
     * スキーマ定義上のTable名の取得
     */
    public function getDefTableName ()
    {
        return static::$table_name;
    }
    /**
     * アプリケーション上でのTable名の取得
     */
    public function getAppTableName ()
    {
        return app()->table->getAppTableNameByClass(get_class($this));
    }
    /**
     * Queryの取得
     */
    public function getQuery ()
    {
        return $this->query;
    }

// -- resultに対するHook

    /**
     * @hook result
     * 条件に対する件数の取得（Limit解除）
     */
    public function result_getTotal ($result)
    {
        if (isset($this->total)) return $this->total;
        // 件数取得用にSQL再発行
        $query = clone($this->query);
        $query["fields"] = array("count"=>"COUNT(*)");
        unset($query["limit"], $query["offset"], $query["order"]);
        $t = $this->createTable($query)->selectOne();
        $this->total = (int)$t["count"];
        return $this->total;
    }
    /**
     * @hook result
     * Pagerの取得
     */
    public function result_getPager ($result)
    {
        if (isset($this->pager)) return $this->pager;
        // limit指定のないSQLに対するPagerは発行不能
        if ( ! $this->query["limit"]) return null;
        $total = $this->result_getTotal($result);
        // Pager組み立て
        $this->pager = new Pager($total, $this->query["offset"], $this->query["limit"]);
        return $this->pager;
    }
    /**
     * @hook result
     * Pagerの取得、但しレコードが1件もなければnullを返す
     */
    public function result_getPagerIfHasRecord ($result)
    {
        $pager = $result->getPager();
        return $pager && $pager->get("count") > 0 ? $pager : null;
    }
    /**
     * @hook result
     * InsertしたレコードのIDを取得
     * ※Insertの発行都度、値が書き換わるのでINSERT直後に確保してある
     */
    public function result_getLastInsertId ($result)
    {
        return $this->last_insert_id;
    }
    /**
     * @hook result
     * 1結果レコードのFetch
     */
    public function result_fetch ($result)
    {
        // Fetch完了済みであれば処理しない
        if ($this->fetch_done) return false;
        // fetch実処理
        $data = $this->getConnection()->fetch($this->result_res);
        // 結果セットの残りがなければ完了済みとする
        if ( ! $data) {
            // on_fetchEnd_*を呼び出す
            $this->callListenerMethod("fetchEnd", array($result));

            app("events")->fire("table.fetch_end", array($this, $this->statement, $result));

            $this->fetch_done = true;
            return false;
        }
        // 結果レコードを組み立てて値をHydrateする
        $record = $result->createRecord();
        $record->hydrate($data);
        // on_fetch_*を呼び出す
        $this->callListenerMethod("fetch", array($record));
        return $record;
    }
    /**
     * @deprecated chunkに統合予定
     * @hook result
     * selectNoFetchと組み合わせて使用する
     * 常にResult上にRecordが1件のみとなるようにして、1結果レコードのFetch
     */
    public function result_fetchNoMap ($result)
    {
        foreach ($result as $i=>$record) unset($result[$i]);
        $record = $result->fetch();
        if ($record) $this->callListenerMethod("fetchEnd", array($result));
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
     * @hook result
     * save処理対象のRecordを取得
     */
    public function result_getSavedRecord ($result)
    {
        if ($this->query->getType()=="update") {
            $id_col_name = $this->getIdColName();
            $id = $this->query->getWhere($id_col_name);
        } elseif ($this->query->getType()=="insert") {
            $id = $result->getLastInsertId();
        }
        return $id ? $this->createTable()->selectById($id) : null;
    }

// -- recordに対するHook

    /**
     * @hook record
     * Hydrate処理
     */
    public function record_hydrate ($record, $data)
    {
        // QueryのFROMとなったテーブル名の確認
        $query_table_name = $this->getQueryTableName();
        // QueryのFROMとなったテーブル以外の値は階層を下げてHydrate
        foreach ((array)$data as $table_name => $values) {
            // $table_data = array();
            foreach ((array)$values as $col_name => $value) {
                if ($query_table_name == $table_name) $record[$col_name] = $value;
                // else $table_data[$col_name] = $value;
                else $record[$table_name.".".$col_name] = $value;
            }
            // if ($query_table_name != $table_name) $record[$table_name] = $table_data;
        }
    }
    /**
     * @hook record
     * IDの設定によりInsert/Update処理
     */
    public function record_save ($record)
    {
        $values = (array)$record;
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
    /**
     * @hook record
     * カラムの値の取得（"テーブル名.カラム名"の様式解決）
     */
    public function record_getColValue ($record, $col_name)
    {
        if (isset($record[$col_name])) return $record[$col_name];
        if (count($parts = explode(".",$col_name))==2) return $record[$parts[0]][$parts[1]];
        return $record[$col_name];
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
     * SELECT文の発行 (Fetchを行わない)
     */
    public function selectNoFetch ($fields=array())
    {
        $this->query->addFields($fields);
        return $this->execQuery("select");
    }
    /**
     * SELECT文の発行 1件取得
     */
    public function selectOne ($fields=array())
    {
        $this->query->addFields($fields);
        $result = $this->execQuery("select");
        $record = $result->fetch();
        if (count($result->fetchAll()) > 1) {
            report_warning("selectOneで複数件取得する処理は値を返しません",array(
                "table" => $this,
            ));
            return null;
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
     * 件数のみ取得するSELECT文を発行
     */
    public function selectCount ()
    {
        $record = $this->fields(array("count"=>"COUNT(*)"))->selectOne();
        return (int)$record["count"];
    }
    /**
     * 特定カラムのみに絞った結果データを取得
     */
    public function selectCol ($col_name)
    {
        $t = $this->fields(array($col_name))->selectOne();
        return $t ? $t[$col_name] : null;
    }
    /**
     * 特定カラムのみに絞った結果データを取得
     */
    public function selectHashedBy ($key_col_name, $key_col_name_sub=false, $col_name_sub_ex=false)
    {
        if ($key_col_name_sub === false) {
            return $this->fields(array($key_col_name))
                ->select()->getHashedBy($key_col_name);
        } elseif ($col_name_sub_ex === false) {
            return $this->fields(array($key_col_name, $key_col_name_sub))
                ->select()->getHashedBy($key_col_name, $key_col_name_sub);
        } else {
            return $this->fields(array($key_col_name, $key_col_name_sub, $col_name_sub_ex))
                ->select()->getHashedBy($key_col_name, $key_col_name_sub, $col_name_sub_ex);
        }
    }
    /**
     * 集計を行った結果データを取得
     */
    public function selectSummary ($summary_field, $key_col_name, $key_col_name_sub=false)
    {
        if ($key_col_name_sub === false) {
            return $this->fields(array("summary"=>$summary_field, $key_col_name))->groupBy($key_col_name)
                ->select()->getHashedBy($key_col_name, "summary");
        } else {
            return $this->fields(array(
                "summary" => $summary_field, $key_col_name, $key_col_name_sub))
                ->groupBy($key_col_name)->groupBy($key_col_name_sub)
                ->select()->getHashedBy($key_col_name, $key_col_name_sub, "summary");
        }
    }

// -- INSERT/UPDATE/DELETE文の発行

    /**
     * idの指定の有無によりINSERT/UPDATE文の発行
     */
    public function save ($values=array())
    {
        $this->query->addValues((array)$values);
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
        $this->query->addValues((array)$values);
        return $this->execQuery("insert");
    }
    /**
     * idを指定してUPDATE文の発行
     */
    public function updateById ($id, $values=array())
    {
        $this->findById($id);
        return $this->updateAll((array)$values);
    }
    /**
     * UPDATE文の発行
     */
    public function updateAll ($values=array())
    {
        $this->query->addValues((array)$values);
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
        if ($this->in_transaction==="begin") return true;
        elseif ($this->in_transaction!==null) {
            report_warning("Transactionは開始済みです",array(
                "table" => $this,
                "in_transaction" => $this->in_transaction,
            ));
            return false;
        }
        $this->in_transaction = "begin";
        return $this->getConnection()->begin();
    }
    /**
     * COMMITの発行
     */
    public function commit ()
    {
        if ($this->in_transaction!=="begin") {
            report_warning("Commit可能なTransactionはありません",array(
                "table" => $this,
                "in_transaction" => $this->in_transaction,
            ));
            return false;
        }
        $this->in_transaction = "commit";
        return $this->getConnection()->commit();
    }
    /**
     * ROLLBACKの発行
     */
    public function rollback ()
    {
        if ($this->in_transaction!=="begin") {
            report_warning("Rollback可能なTransactionはありません",array(
                "table" => $this,
                "in_transaction" => $this->in_transaction,
            ));
            return false;
        }
        $this->in_transaction = "rollback";
        return $this->getConnection()->rollback();
    }

// -- SQL組み立て/発行実処理

    /**
     * Queryを完成させる
     */
    public function buildQuery ($type=null)
    {
        // 作成済みであればSQL文を返す
        if ($this->statement) return $this->statement;
        // typeの指定を確認する
        if ($type) $this->query->setType($type);
        $type = $this->query->getType();
        if ( ! $type) {
            report_error("組み立てるQueryのtypeが指定されていません",array(
                "table" => $this,
            ));
        }
        // Query組み立て処理を呼び出す
        $this->callBuildQueryMethods();
        // SQL組み立て
        return $this->statement = new Statement($this);
    }
    /**
     * SQLの発行実処理
     */
    public function execQuery ($type=null)
    {
        // 実行済みであれば結果を返す
        if ( ! $this->result_res) {
            // Trunsactionの自動開始
            if (static::$auto_begin && $this->in_transaction===null) $this->begin();
            // Query組み立ての仕上げ処理
            $statement = $this->buildQuery($type);
            // SQLの実行
            $this->result_res = $this->getConnection()->exec($statement, array("table"=>$this));
        }
        // Resultの組み立て
        $result = new Result($this);
        // LastInsertIdの確保
        if ($type=="insert") {
            if (static::$cols[$this->getIdColName()]["autoincrement"]) {
                $this->last_insert_id = $this->getConnection()
                    ->lastInsertId(static::$table_name, $this->getIdColName());
            } else {
                $this->last_insert_id = $this->query->getvalue($this->getIdColName());
            }
        }
        // on_afterWrite_*を呼び出す
        if ($type=="insert" || $type=="update") {
            $this->callListenerMethod("afterWrite", array($this->query, $result));
        }
        // Trunsactionの自動完了
        if (static::$auto_commit && $this->in_transaction==="begin") $this->commit();
        return $result;
    }

// -- hookメソッド呼び出し関連処理

    /**
     * Result上の値の読み込み前処理
     */
    public function beforeGetResultValue ($index)
    {
    }
    /**
     * Record上の値の読み込み前処理
     */
    public function beforeGetRecordValue ($record, $col_name)
    {
        $this->callListenerMethod("getBlankCol", array($record, $col_name));
    }
    /**
     * buildQuery上で必要になるon_*_*処理をまとめて呼び出す
     */
    private function callBuildQueryMethods ()
    {
        // 呼び出すHookを選択
        $hooks = array();
        $type = $this->query->getType();
        // type別
        $hooks[] = $type;
        // valuesを持つQuery
        if ($type=="insert" || $type=="update") $hooks[] = "write";
        // whereを持つQuery
        if ($type=="select" || $type=="update") $hooks[] = "read";
        // 全て
        $hooks[] = "any";
        // 呼び出す
        $this->callListenerMethod($hooks,array($this->query));
    }
    /**
     * on hookメソッドを呼び出す
     */
    protected function callListenerMethod ($hooks, $args=array())
    {
        $hooks = is_array($hooks) ? $hooks : array($hooks);
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
        $method_names = array();
        foreach ($hooks as $hook) $method_names = array_merge($method_names, (array)$defined[$hook]);
        usort($method_names, function($a, $b){
            $a_priority = preg_match('!_(\d+)$!', $a, $_) ? $_[1] : 500;
            $b_priority = preg_match('!_(\d+)$!', $b, $_) ? $_[1] : 500;
            return $a_priority>$b_priority ? +1 : -1;
        });
        foreach ($method_names as $method_name) {
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
                app("events")->fire("table.hook", array($this, $this->statement, null,
                    $method_name, $args));
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
        );
    }

    private $__release_status = 0;
    private $__release_pool = array();
    /**
     * メモリ解放
     */
    public function __release ()
    {
        if ($this->__release_status) return;
        $this->__release_status = 1;
        if ($this->statement) {
            $this->statement->__release();
            unset($this->statement);
        }
        if ($this->query) {
            $this->query->__release();
            unset($this->query);
        }
        // if ($this->result) {
        //     $this->result->__release();
        //     unset($this->result);
        // }
        foreach ($this->__release_pool as $object) {
            $object->__release();
        }
    }
    /**
     * 同時にメモリ解放すべきオブジェクトの収集
     */
    protected function releasable ($object)
    {
        //$this->__release_pool[] = $object;
        return $object;
    }
    /**
     * 同時にメモリ解放すべきオブジェクトの収集
     */
    public function scoped ($callback)
    {
        $values = $callback($this);
        //$this->__release();
        return $values;
    }
    // public function __destruct ()
    // {
    //     $this->__callee = app("memory_usage")->getCalleeLocation(7);
    //     print "(".static::$table_name.":".$this->__callee.")";
    // }
}
