<?php
namespace R\Lib\Table;

use R\Util\Reflection;
use R\Lib\DBI\Model_Base;
use R\Lib\DBI\DBI_Base;

/**
 * Tableクラスの継承元
 */
class Table_Base extends Table_Core
{
    /**
     * @hook assoc hasMany
     * パラメータ例:
     *     "assoc"=>"hasMany",
     *     "table"=>"Product", // 必須 関係先テーブル名
     *     "fkey"=>"owner_member_id", // 任意 関係先テーブル上のFK
     *  読み込み時の動作:
     *      Fetch完了後、結果全てのPKで関係先テーブルをSelectする
     *  書き込み時の動作:
     *      ※書き込んだIDがわかることが必須なので、IDを指定しないUpdateではエラー
     *      対象のIDに関係する関係先のレコードを全件削除
     *      登録対象のレコードを順次Insert
     */
    public function assoc_afterFetchEnd_hasMany ($col_name)
    {
        $assoc_table_name = static::$cols[$col_name]["table"];
        $assoc_fkey = static::$cols[$col_name]["fkey"];
        if ( ! $assoc_table_name || ! $assoc_fkey) {
            report_error("パラメータの指定が不足しています",array(
                "col_name" => $col_name,
                "params" => static::$cols[$col_name],
                "required" => array("table","fkey"),
            ));
        }
        // 主テーブルのIDを取得
        $pkey = $this->getIdColName();
        $ids = $this->result->getHashedBy($pkey);
        // 関連テーブルをFkeyでSELECT
        $assoc_result_set = table($assoc_table_name)
            ->findBy($assoc_fkey, $ids)
            ->select()
            ->getGroupedBy($assoc_fkey);
        // 主テーブルのResultに関連づける
        foreach ($this->result as $i => $record) {
            $this->result[$i][$col_name] = (array)$assoc_result_set[$record[$pkey]];
        }
    }
    public function assoc_afterWrite_hasMany ($col_name, $values)
    {
        $assoc_table_name = static::$cols[$col_name]["table"];
        $assoc_fkey = static::$cols[$col_name]["fkey"];
        if ( ! $assoc_table_name || ! $assoc_fkey) {
            report_error("パラメータの指定が不足しています",array(
                "col_name" => $col_name,
                "params" => static::$cols[$col_name],
                "required" => array("table","fkey"),
            ));
        }
        // 書き込んだIDを確認
        $id = $this->result->getLastSaveId();
        // 対象のIDに関係する関係先のレコードを全件削除
        table($assoc_table_name)
            ->findBy($assoc_fkey, $id)
            ->deleteAll();
        // 複数レコードの同時Updateに対応
        if ( ! is_array($id)) {
            $id = array($id);
        }
        foreach ($id as $id_one) {
            // 登録対象のレコードを順次Insert
            foreach ($values as $i => $record) {
                $record[$assoc_fkey] = $id_one;
                table($assoc_table_name)->insert($record);
            }
        }
    }

    /**
     * @hook assoc hasMany
     * パラメータ例:
     *     "assoc"=>"hasMany",
     *     "table"=>"Product", // 必須 関係先テーブル名
     *     "fkey"=>"owner_member_id", // 任意 関係先テーブル上のFK
     *  読み込み時の動作:
     *      Fetch完了後、結果全てのPKで関係先テーブルをSelectする
     *  書き込み時の動作:
     *      ※書き込んだIDがわかることが必須なので、IDを指定しないUpdateではエラー
     *      対象のIDに関係する関係先のレコードを全件削除
     *      登録対象のレコードを順次Insert
     */
    public function assoc_afterFetchEnd_hasManyValues ($col_name)
    {
    }

    /**
     * @hook chain
     */
    public function chain_with ($col_name, $col_name_sub=false)
    {
        if ($col_name_sub === false) {
            $this->query->addField($col_name);
        } else {
            $this->query->addField($col_name, $col_name_sub);
        }
    }

    /**
     * @hook chain
     */
    public function chain_join ($table, $alias=null, $on=array(), $type="LEFT")
    {
        $this->query->join($table, $alias, $on, $type);
    }

    /**
     * @hook chain
     * GROUP BY句の設定
     */
    public function chain_groupBy ($col_name)
    {
        $this->query->addGroupBy($col_name);
    }

    /**
     * @hook chain
     * ORDER BY句の設定
     */
    public function chain_orderBy ($col_name, $asc=true)
    {
        $this->query->addOrderBy($col_name.($asc ? " ASC" : " DESC"));
    }

    /**
     * @hook chain
     * OFFSET/LIMIT句の設定
     */
    public function chain_pagenate ($offset=false, $limit=false)
    {
        if ($offset !== false) {
            $this->query->setOffset($offset);
        }
        if ($limit !== false) {
            $this->query->setLimit($limit);
        }
    }

    /**
     * @hook chain
     */
    public function chain_findById ($id)
    {
        $this->findBy($this->getIdColName("id"), $id);
    }

    /**
     * @hook chain
     */
    public function chain_findBy ($col_name, $value=false)
    {
        $this->query->where($col_name, $value);
    }

    /**
     * @hook chain
     */
    public function chain_findMine ()
    {
        $account = auth()->getAccount();
        if ( ! $account->isLogin()) {
            return $this->findNothing();
        }
        // アカウントのテーブル名の解決
        $table_name = $account->getAttr("table_name");
        if ( ! $table_name) {
            $table_name = str_camelize($account->getRole());
        }
        // 関係先を条件に取得
        $id = $account->getId();
        $this->findByAssoc($table_name, $id);
    }

    /**
     * @hook chain
     */
    public function chain_findByLoginIdPw ($login_id, $login_pw)
    {
        $login_id_col_name = $this->getColNameByAttr("login_id");
        $login_pw_col_name = $this->getColNameByAttr("login_pw");
        if ( ! $login_id_col_name || ! $login_pw_col_name) {
            report_error("login_id,login_pwカラムがありません",array("class" => get_class($this)));
        }
        $this->query->where($login_id_col_name, (string)$login_id);
        $this->query->where($login_pw_col_name, md5($login_pw));
    }

    /**
     * @hook chain
     */
    public function chain_findByAssoc ($assoc_table, $assoc_id)
    {
        $fkey_col_name = $this->getFkeyColName($assoc_table);

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
     * @hook chain
     * 絞り込み結果を空にする
     */
    public function chain_findNothing ()
    {
        $this->query->where("0=1");
    }

    /**
     * @hook chain
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
    protected function buildQuery_read_attachDelFlg ()
    {
        if ($del_flg_col_name = $this->getColNameByAttr("del_flg")) {
            $this->query->where($del_flg_col_name, 0);
        }
    }
    protected function buildQuery_update_attachDelFlg ()
    {
        if ($del_flg_col_name = $this->getColNameByAttr("del_flg")) {
            if ($this->query->getDelete()) {
                $this->query->setDelete(false);
                $this->query->setValue($del_flg_col_name, 1);
            }
        }
    }
}

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
    protected static $ds_name = null;
    protected static $def = array();
    protected static $cols = array();

    /**
     * callHookMethodの対象メソッド一覧
     * クラス定義から収集するので2回目以降変更がないため保持
     */
    protected static $hook_defined = null;

    /**
     * Hook処理の呼び出し履歴
     */
    private $hook_history = array();

    /**
     * DBIのSQL実行結果リソース
     */
    private $result_res = null;

    /**
     * 定義から収集した外部キーのキャッシュ
     */
    protected static $fkey_defined = null;

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
        if ($this->callHookMethod($chain_method_name, $args)) {
            return $this;
        }

        // MEMO: Queryの操作はTableクラス外から行えない
        // Tableクラス内にQuery操作用の抽象メソッドを定義する動機である

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
     * ID属性の指定されたカラム名の取得
     */
    protected function getIdColName ()
    {
        $id_col_name = $this->getColNameByAttr("id");
        if ( ! $id_col_name) {
            report_error("idカラムが定義されていません",array(
                "table_class"=>get_class($this)
            ));
        }
        return $id_col_name;
    }

    /**
     * 外部キーとして利用するカラム名の取得
     */
    protected function getFkeyColName ($assoc_name)
    {
        // 定義から外部キーを収集
        if ( ! isset(static::$fkey_defined)) {
            static::$fkey_defined = array();
            // 自分のテーブルでIDを参照できるようにする
            static::$fkey_defined[self::$table_name] = $this->getIdColName();
            // fkey_for属性から参照できるようにする
            foreach (static::$cols as $col_name => $col) {
                // 文字列
                if (is_string($col["fkey_for"])) {
                    static::$fkey_defined[$col["fkey_for"]] =$col_name;
                // 配列で複数指定可能
                } elseif (is_array($col["fkey_for"])) {
                    foreach ($col["fkey_for"] as $fkey_for) {
                        static::$fkey_defined[$fkey_for] =$col_name;
                    }
                }
            }
        }
        return static::$fkey_defined[$assoc_name];
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
            // assocの関連づけ処理（assoc_afterFetchEnd）を呼び出す
            $this->assoc_afterFetchEnd();

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

        // assocの関連づけ処理（assoc_afterFetchEach）を呼び出す
        $this->assoc_afterFetchEach($record);

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
     * Pagerの取得
     */
    public function result_getPager ($result)
    {
        // Pager取得用にSQL再発行
        $query_array = (array)$this->buildQuery("select");
        return $this->getDBI()->select_pager($query_array);
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
                "class" => get_class($this),
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
            report_error("IDが複数指定されています",array(
                "class" => get_class($this),
            ));
        }
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
    public function select ($fields=null)
    {
        $this->query->addFields($fields);
        $this->execQuery("select");
        return $this->result->fetchAll();
    }

    /**
     * SELECT文の発行 Fetch/マッピングを行わない
     */
    public function selectNoFetch ($fields=array())
    {
        $this->query->addFields($fields);
        $this->query->setNoFetch(true);
        $this->execQuery("select");
        return $this->result;
    }

    /**
     * idの指定の有無によりINSERT/UPDATE文の発行
     */
    public function save ($values=array(), $id=null)
    {
        $this->query->addValues($values);
        // ValuesでIDが指定されていれば削除してIDを条件に指定する
        $id_col_name = $this->getIdColName();
        if ( ! $id && $id_value = $this->query->getValue($id_col_name)) {
            $this->query->removeValue($id_col_name);
            $id = $id_value;
        }
        // IDが指定されていればUpdate、指定が無ければInsert
        return $id ? $this->updateById($id) : $this->insert();
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
                "class" => get_class($this),
                "query" => $this->query,
            ));
        }

        // テーブル名を関連づける
        $this->query->setTable(static::$table_name);

        // Query組み立て処理（buildQuery_*）を呼び出す
        $suffixes = array("");
        // 認証時の処理呼び出し追加
        if (auth()->checkAuthenticated()) {
            $suffixes[] = "As".str_camelize(auth()->getAccount()->getRole());
        }
        foreach ($suffixes as $suffix) {
            // type別
            $this->callHookMethod("buildQuery_".$type.$suffix."_*", array());
            // valuesを持つQuery
            if ($type=="insert" || $type=="update") {
                $this->callHookMethod("buildQuery_write".$suffix."_*", array());
            }
            // whereを持つQuery
            if ($type=="select" || $type=="update") {
                $this->callHookMethod("buildQuery_read".$suffix."_*", array());
            }
            // 全て
            $this->callHookMethod("buildQuery_any".$suffix."_*", array());
        }

        // assocの関連づけ処理（assoc_before*）を呼び出す
        if ($type=="insert" || $type=="update") {
            $this->assoc_beforeWrite();
        }
        if ($type=="select") {
            $this->assoc_beforeSelect();
        }

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
            "Type" =>$type,
            "Query" =>$this->query,
            "History" =>(array)$this->hook_history,
        ));
        // Resultの組み立て
        $this->result = new Result($this);

        // assocの関連づけ処理（assoc_afterWrite）を呼び出す
        if ($type=="insert" || $type=="update") {
            $this->assoc_afterWrite();
        }

        return $this->result;
    }

    /**
     * assoc処理 selectの発行前
     */
    private function assoc_beforeSelect ()
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
                $this->callHookMethod("assoc_beforeSelect_".$assoc, array($col_name));
            }
        }
    }

    /**
     * assoc処理 各レコードfetch後
     */
    private function assoc_afterFetchEach ($record)
    {
        foreach ((array)$this->query->getAssocFields() as $col_name) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_afterFetchEach_".$assoc, array($col_name, $record));
        }
    }

    /**
     * assoc処理 fetch完了後
     */
    private function assoc_afterFetchEnd ()
    {
        foreach ((array)$this->query->getAssocFields() as $col_name) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_afterFetchEnd_".$assoc, array($col_name));
        }
    }

    /**
     * assoc処理 insert/updateの発行前
     */
    public function assoc_beforeWrite ()
    {
        foreach ((array)$this->query->getValues() as $col_name => $value) {
            if ($assoc = static::$cols[$col_name]["assoc"]) {
                // values→assoc_valuesに項目を移動
                $this->query->removeValue($col_name);
                $this->query->setAssocValue($col_name,$value);
                // assoc処理の呼び出し
                $this->callHookMethod("assoc_beforeWrite_".$assoc, array($col_name,$value));
            }
        }
    }

    /**
     * assoc処理 insert/updateの発行後
     */
    public function assoc_afterWrite ()
    {
        foreach ((array)$this->query->getAssocValues() as $col_name => $value) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_afterWrite_".$assoc, array($col_name,$value));
        }
    }

    /**
     * Hookメソッドを呼び出す
     */
    private function callHookMethod ($method_name, $args=array())
    {
        // 定義されているメソッド名を収集
        if ( ! isset(static::$hook_defined)) {
            static::$hook_defined = array();
            foreach (get_class_methods($this) as $hook_method_name) {
                $pattern = explode("_",$hook_method_name);
                if (count($pattern)>1) {
                    // 分解して登録
                    $ref = & static::$hook_defined;
                    foreach ($pattern as $part) {
                        if ( ! $part) {
                            continue 2;
                        }
                        $ref = & $ref[$part];
                    }
                    $ref["_"] = $hook_method_name;
                }
            }
        }

        $matched = array();

        // パターンにマッチするメソッドを探索する
        if (preg_match('!_\*$!',$method_name)) {
            $ref = & static::$hook_defined;
            foreach (explode("_",$method_name) as $part) {
                if (isset($ref[$part])) {
                    $ref = & $ref[$part];
                } elseif ($part == "*") {
                    foreach ($ref as $v) {
                        if (isset($v["_"])) {
                            $matched[] = $v["_"];
                        }
                    }
                    break;
                } else {
                    $matched = array();
                    break;
                }
            }
        } elseif (method_exists($this, $method_name)) {
            $matched[] = $method_name;
        }

        // マッチしたメソッドを呼び出す
        foreach ($matched as $hook_method_name) {
            $result = call_user_func_array(array($this,$hook_method_name),$args);
            // 履歴への登録
            if ($result!==false) {
                $this->hook_history[] = $method_name;
            }
        }
        return $matched;
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
