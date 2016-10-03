<?php
namespace R\Lib\Table;

use R\Util\Reflection;
use R\Lib\DBI\Model_Base;
use R\Lib\DBI\DBI_Base;

/**
 * Tableクラスの継承元
 */
class Table_Base
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
     * buildQueryの処理が完了しているかどうか
     * 2回目の呼び出しで処理を呼ばないために使う
     */
    private $build_query_done = false;

    /**
     * buildQueryの処理メソッド一覧
     * クラス定義から収集するので2回目以降変更がないので保持する
     */
    protected static $build_query_methods = null;

    /**
     * Query書き換え処理の呼び出し履歴
     */
    private $build_query_history = array();

    /**
     * DBIのSQL実行結果リソース
     */
    private $result_res = null;

    /**
     * テーブルの定義
     */
    protected static $table_name = null;
    protected static $ds_name = null;
    protected static $def = array();
    protected static $cols = array();
    protected static $refs = array();

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
            $result = call_user_func_array(array($this,$chain_method_name), $args);
            // 履歴の登録
            $this->build_query_history[] = $chain_method_name;

            return $this;
        }

        // queryのメソッド呼び出し
        if (method_exists($this->query, $method_name)) {
            $result = call_user_func_array(array($this->query,$method_name), $args);
            // 履歴の登録
            $this->build_query_history[] = $method_name;

            return isset($result) ? $result : $this;
        }

        report_error("メソッドの定義がありません",array(
            "class" => get_class($this),
            "method_name" => $method_name,
            "chain_method_name" => $chain_method_name,
        ));
    }

    /**
     * Recordオブジェクトを作成する
     */
    public function createRecord ($values=null)
    {
        $record = new Record($this);

        if (isset($values)) {
            foreach ($values as $k => $v) {
                $record[$k] = $v;
            }
        }

        return $record;
    }

    /**
     * ID属性の指定されたカラム名の取得
     */
    protected function findIdColName ()
    {
        $id_col_name = $this->findColNameByAttr("id");
        if ( ! $id_col_name) {
            report_error("idカラムが定義されていません",array("table_class"=>get_class($this)));
        }
        return $id_col_name;
    }

    /**
     * 外部キーとして利用するカラム名の取得
     */
    protected function findFkeyColName ($table_name)
    {
        return static::$refs[$table_name];
    }

    /**
     * 属性の指定されたカラム名の取得
     */
    protected function findColNameByAttr ($attr)
    {
        foreach (static::$cols as $col_name => $col) {
            if ($col[$attr]) {
                return $col_name;
            }
        }
        return null;
    }

    /**
     * @chain
     */
    public function chain_findById ($id)
    {
        $this->query->where($this->findIdColName("id"), $id);
    }

    /**
     * @chain
     */
    public function chain_findBy ($col_name, $value)
    {
        $this->query->where($col_name, $value);
    }

    /**
     * @chain
     */
    public function chain_findMine ()
    {
        $account = auth()->getAccessAccount();

        if ( ! $account) {
            report_warning("認証されていません");
            return $this->findNothing();
        }

        $id = $account->getId();

        $table_name = $account->getAttr("table_name");

        if ( ! $table_name) {
            $table_name = str_camelize($account->getRole());
        }

        $this->findByAssoc($table_name, $id);
    }

    /**
     * @chain
     */
    public function chain_findByAssoc ($assoc_table, $assoc_id)
    {
        $fkey_col_name = $this->findFkeyColName($assoc_table);

        if ( ! $fkey_col_name) {
            report_warning("外部キーが定義されていません",array(
                "table_class" => get_class($this),
                "assoc_table" => $assoc_table,
            ));
            return $this->findNothing();
        }

        $this->findBy($fkey_col_name, $assoc_id);
    }

    /**
     * @chain
     * 絞り込み結果を空にする
     */
    public function chain_findNothing ()
    {
        $this->query->where("0=1");
    }

    /**
     * @chain
     * 検索フォームによる絞り込み
     */
    public function chain_findBySearchForm ($list_setting, $input)
    {
        $query_array = $this->getModel()->get_list_query($list_setting, $input);
        $this->query->merge($query_array);
    }

    /**
     * @hook buildQuery
     * 削除フラグを関連づける
     */
    protected function buildQuery_attachDelFlg ()
    {
        if ($del_flg_col_name = $this->findColNameByAttr("del_flg")) {
            $this->query->where($del_flg_col_name, 0);
        }
    }

    /**
     * @hook result
     * 1結果レコードのFetch
     */
    public function result_fetch ($result)
    {
        $ds = $this->getDBI()->get_datasource();
        $ds->_result =$this->result_res;

        // 結果セットが無効であればnullを返す
        if ( ! $ds->hasResult()) {
            return null;
        }

        $ds->resultSet($ds->_result);
        $data =$ds->fetchResult();

        // データがなければnullを返す
        if ( ! $data) {
            return null;
        }

        // 結果レコードの組み立て
        $record = $this->createRecord();
        $record->hydrate($data);

        $result[] = $record;

        return $record;
    }

    /**
     * @hook result
     * 全結果レコードのFetch
     */
    public function result_fetchAll ($result)
    {
        while ($result->fetch());
        return $result;
    }

    /**
     * @hook result
     * Pagerの取得
     */
    public function result_getPager ($result)
    {
        // Pager取得用にSQL再発行
        $query_array = (array)$this->buildQuery("select");
        $pager = $this->getDBI()->select_pager($query_array);

        return $pager;
    }

    /**
     * @hook record
     * Hydrate処理
     */
    public function record_hydrate ($record, $data)
    {
        // hydrate
        foreach ((array)$data as $k1 => $v1) {
            foreach ((array)$v1 as $k2 => $v2) {
                $record[$k2] = $v2;
            }
        }
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
        $this->query->fields($fields);

        $this->execQuery("select");

        return $this->result->fetch();
    }

    /**
     * SELECT文の発行 全件取得してハッシュを作成
     */
    public function selectHash ($k, $v=false)
    {
        $this->query->field($k);
        if ($v!==false) {
            $this->query->field($v);
        }

        $ts = $this->select();

        $hash =array();
        foreach ($ts as $t) {
            if ($v!==false) {
                $list[$t[$k]] =$t[$v];
            } else {
                $list[] =$t[$k];
            }
        }
        return $hash;
    }

    /**
     * SELECT文の発行 全件取得
     */
    public function select ($fields=null)
    {
        if (isset($fields)) {
            $this->query->setFields($fields);
        }

        $this->execQuery("select");

        $ts = $this->result->fetchAll();
        return $ts;
    }

    /**
     * SELECT文の発行 Pagenate取得
     */
    public function selectPagenate ($fields=null)
    {
        if (isset($fields)) {
            $this->query->setFields($fields);
        }

        $this->query->setPagenate(true);
        $this->execQuery("select");

        $ts = $this->result->fetchAll();
        $p = $this->result->getPager();
        return array($ts,$p);
    }

    /**
     * SELECT文の発行 Fetchは行わない
     */
    public function selectNoFetch ($fields=array())
    {
        $this->query->fields($fields);
        return $this->execQuery("select");
    }

    /**
     * idの指定の有無によりINSERT/UPDATE文の発行
     */
    public function save ($id, $values=null)
    {
        if (strlen($id)) {
            return $this->updateById($id, $values);
        } else {
            return $this->insert($values);
        }
    }

    /**
     * INSERT文の発行
     */
    public function insert ($values=null)
    {
        if (isset($values)) {
            $this->query->setValues($values);
        }
        return $this->execQuery("insert");
    }

    /**
     * idを指定してUPDATE文の発行
     */
    public function updateById ($id, $values=null)
    {
        $this->findById($id);
        return $this->updateAll($values);
    }

    /**
     * UPDATE文の発行
     */
    public function updateAll ($values=null)
    {
        if (isset($values)) {
            $this->query->setValues($values);
        }
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
     * Queryの発行
     */
    private function execQuery ($type)
    {
        // Query組み立ての仕上げ処理
        $this->buildQuery($type);

        // UDPATEを物理削除に切り替え
        if ($type=="update" && $this->query->getDelete()) {
            $type = "delete";
        }

        // SQL文の作成と発行
        $query_array = (array)$this->query;
        $st_method = "st_".$type;
        $st =$this->getDBI()->$st_method($query_array);
        $this->result_res =$this->getDBI()->exec($st,array(
            "Type" =>$type,
            "Query" =>$query_array,
            "History" =>(array)$this->build_query_history,
        ));

        // Resultの組み立て
        $this->result = new Result($this);

        return $this->result;
    }

    /**
     * Queryを完成させる
     */
    private function buildQuery ($type)
    {
        // 組み立て済みであればQueryを返す
        if ($this->build_query_done) {
            return $this->query;
        }

        // テーブル名を関連づける
        $this->query->setTable(static::$table_name);

        // Query組み立て処理を呼び出す
        $suffixes = array("");
        // 認証時の処理呼び出し追加
        if (auth()->checkAuthenticated()) {
            $suffixes[] = "As".str_camelize(auth()->getAccount()->getRole());
        }
        foreach ($suffixes as $suffix) {
            $this->callBuilQueryMethods($type.$suffix);
            if ($type=="insert" || $type=="update") {
                $this->callBuilQueryMethods("write".$suffix);
            }
            if ($type=="select" || $type=="update") {
                $this->callBuilQueryMethods("read".$suffix);
            }
            $this->callBuilQueryMethods("any".$suffix);
        }
        $this->build_query_done = true;

        return $this->query;
    }

    /**
     * メソッド名を前方一致で全て呼び出す
     */
    private function callBuilQueryMethods ($group)
    {
        // 定義されているメソッド名を収集
        if ( ! isset(static::$build_query_methods)) {
            static::$build_query_methods = array();
            foreach (get_class_methods($this) as $method_name) {
                if (strpos($method_name, "buildQuery_")===0) {
                    $parts = explode("_",$method_name);
                    static::$build_query_methods[$parts[1]][] = $method_name;
                }
            }
        }

        foreach ((array)static::$build_query_methods[$group] as $method_name) {
            $result = call_user_func_array(array($this,$method_name),array());
            // 履歴への登録
            if ($result!==false) {
                $this->build_query_history[] = $method_name;
            }
        }

    }

    /**
     * Table定義の取得
     */
    public function getTableDef ()
    {
        $table_def = (array)static::$def;
        $table_def["table_name"] = static::$table_name;
        $table_def["ds_name"] = static::$ds_name;
        $table_def["cols"] = (array)static::$cols;
        return $table_def;
    }

    /**
     * @deprecated
     * Modelオブジェクトの取得
     */
    private function getModel ()
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
            register_shutdown_webapp_function("dbi_rollback_all");
            define("DBI_LOADED",true);
        }

        $instance = & ref_globals("loaded_dbi");
        $name =$this->ds_name ? $this->ds_name : "default";

        if ( ! $instance[$name]) {
            $connect_info =registry("DBI.connection.".$name);
            $instance[$name] =new DBI_Base($name);
            $instance[$name]->connect($connect_info);
        }
        return $instance[$name];
    }
}
