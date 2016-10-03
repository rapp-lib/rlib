<?php
namespace R\Lib\Query;

use R\Util\Reflection;
use R\Lib\DBI\Model_Base;
use R\Lib\DBI\DBI_Base;

/**
 * Tableクラスの継承元
 */
class Table_Base
{
    protected $config;
    protected $query;
    protected $result;
    protected $is_build_done = false;

    protected static $table_name = null;
    protected static $ds_name = null;

    protected static $def = array();
    protected static $cols = array();
    protected static $refs = array();

    /**
     * @override
     */
    public function __construct ($config=array())
    {
        $this->config = $config;
        $this->query = new Query;
        $this->result = null;

        if ( ! static::$table_name) {
            if (preg_match('!([A-Z][a-zA-Z0-9]+)Table$!',get_class($this),$match)) {
                static::$table_name = $match[1];
            }
        }

        if ( ! static::$ds_name) {
            static::$ds_name = "default";
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
            $result = call_user_func_array(array($this,$chain_method_name), $args);

            return isset($result) ? $result : $this;
        }

        // xxx_の形式であれば前方一致で全て呼び出す
        if (preg_match('!_$!',$method_name,$match)) {
            $method_names = Reflection::getMethodNames($this);
            foreach ($method_names as $check_method_name) {
                if (preg_match('!^'.$method_name.'.+!',$check_method_name)) {
                    call_user_func_array(array($this,$check_method_name),$args);
                }
            }

            return $this;
        }

        // queryのメソッド呼び出し
        if (method_exists($this->query, $method_name)) {
            $result = call_user_func_array(array($this->query,$method_name), $args);

            return isset($result) ? $result : $this;
        }

        report_error("メソッドの定義がありません",array(
            "class" => get_class($this),
            "method_name" => $method_name,
            "chain_method_name" => $chain_method_name,
        ));
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
     */
    public function chain_findNothing ()
    {
        $this->query->where("0=1");
    }

    /**
     * @chain
     */
    public function chain_findBySearchForm ($list_setting, $input)
    {
        $query_array = $this->getModel()->get_list_query($list_setting, $input);
        $this->query->merge($query_array);
    }

    /**
     * Queryを完成させる
     */
    protected function buildQuery ($type=null)
    {
        if ($type) {
            $this->query->setType($type);
        }
        if ( ! $type) {
            report_error("組み立てるQueryの種類の指定がありません",array(
                "query" => $this->query,
                "table" => $this,
            ));
        }

        // 1回だけbuildQuery_*の呼び出しを行う
        if ( ! $this->is_build_done) {
            $this->buildQuery_();
            $this->is_build_done = true;
        }
        return $this->query;
    }

    /**
     * @hook buildQuery
     * テーブル名を関連づける
     */
    protected function buildQuery_attachTableName ()
    {
        $this->query->table(static::$table_name);
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
     * @hook buildQuery
     * スキーマ定義にない値の設定を削除
     */
    protected function buildQuery_filterValues ()
    {
        foreach ((array)$this->query["values"] as $k => $v) {
            if (static::$cols[$k] && ! static::$cols[$k]["type"]) {
                $this->query->removeValues($k);
            }
        }
    }

    /**
     * Hydrate処理
     */
    public function resultRecord_hydrate ($record, $result)
    {
        // hydrate
        foreach ((array)$result as $k1 => $v1) {
            foreach ((array)$v1 as $k2 => $v2) {
                $record[$k2] = $v2;
            }
        }
    }

    /**
     * @hook result
     * getDBIを呼び出す
     */
    public function result_getDBI ()
    {
        return $this->getDBI();
    }

    /**
     * Queryの発行
     */
    public function execQuery ($type=false)
    {
        // Query組み立ての仕上げ処理
        $this->buildQuery($type);

        $type = $this->query->getType();

        // SQL文の作成と発行
        $query_array = (array)$this->query;
        $st_method = "st_".$type;
        $st =$this->getDBI()->$st_method($query_array);
        $result =$this->getDBI()->exec($st,array(
            "Type" =>$type,
            "Query" =>$query_array,
        ));

        // Resultの組み立て
        $this->result = new QueryResult($result, $this);

        // Pager取得用にSQL再発行
        if ($type=="select" && $this->query->getPager()) {
            $pager = $this->getDBI()->select_pager($query_array);
            $this->result->setPager($pager);
        }

        return $this->result;
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
    public function select ($fields=array())
    {
        $this->query->fields($fields);

        $this->execQuery("select");

        return $this->result->fetchAll();
    }

    /**
     * SELECT文の発行 Pagenate取得
     */
    public function selectPagenate ($fields=array())
    {
        $this->query->fields($fields);

        $this->query->setPager(true);

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
    public function save ($id, $values=array())
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
    public function insert ($values=array())
    {
        $this->query->values($values);

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
        $this->query->values($values);

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
        return $this->execQuery("delete");
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
     * Table定義の取得
     */
    public function getTableDef ()
    {
        $table_def = static::$def;
        $table_def["cols"] = static::$cols;

        // テーブル定義名の補完
        if ( ! $table_def["table_name"]) {
            $table_def["table_name"] = static::$table_name;
        }

        // DS名の補完
        if ( ! $table_def["ds_name"]) {
            $table_def["ds_name"] = static::$ds_name;
        }

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
