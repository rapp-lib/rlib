<?php
namespace R\Lib\Table\Feature\Provider;
use R\Lib\Table\Feature\BaseFeatureProvider;

class QueryExec extends BaseFeatureProvider
{
    /**
     * Query組み立て
     */
    public function chainEnd_renderSelect($query)
    {
        $query->setType("select");
        return $query->render();
    }

// -- SELECT文の発行

    /**
     * SELECT文の発行 全件取得
     */
    public function chainEnd_select($query)
    {
        $query->setType("select");
        return app("table.query_executer")->execFetchAll($query);
    }
    /**
     * SELECT文の発行 1件取得
     */
    public function chainEnd_selectOne($query)
    {
        $result = $this->chainEnd_select($query);
        if (count($result) > 1) {
            report_warning("selectOneで複数件取得する処理は値を返しません",array(
                "query"=>$query,
            ));
            return null;
        }
        return $result[0];
    }
    /**
     * idを指定してSELECT文の発行 1件取得
     */
    public function chainEnd_selectById($query, $id)
    {
        return $query->makeBuilder()->findById($id)->selectOne();
    }
    /**
     * 件数のみ取得するSELECT文を発行
     */
    public function chainEnd_selectCount($query)
    {
        $query->setFields(array("count"=>"COUNT(*)"));
        $record = $query->makeBuilder()->selectOne();
        return (int)$record["count"];
    }
    /**
     * 特定カラムのみに絞った結果データを取得
     */
    public function chainEnd_selectCol($query, $col_name)
    {
        $query->setFields(array($col_name));
        $record = $query->makeBuilder()->selectOne();
        return $record ? $record[$col_name] : null;
    }

// -- INSERT/UPDATE/DELETE文の発行

    /**
     * INSERT文の発行
     */
    public function chainEnd_insert($query, $values=array())
    {
        if ($values) $query->addValues($values);
        $query->setType("insert");
        return app("table.query_executer")->exec($query);
    }
    /**
     * UPDATE文の発行
     */
    public function chainEnd_updateAll($query, $values=array())
    {
        $query->addValues($values);
        $query->setType("update");
        return app("table.query_executer")->exec($query);
    }
    /**
     * idを指定してUPDATE文の発行
     */
    public function chainEnd_updateById($query, $id, $values=array())
    {
        $query->makeBuilder()->findById($id);
        return $this->chainEnd_updateAll($query, $values);
    }
    /**
     * DELETE文の発行
     */
    public function chainEnd_deleteAll($query, $delete_type="soft")
    {
        $query->setDelete($delete_type);
        $query->setType("update");
        return app("table.query_executer")->exec($query);
    }
    /**
     * idを指定してDELETE文の発行
     */
    public function chainEnd_deleteById($query, $id, $delete_type="soft")
    {
        $query->makeBuilder()->findById($id);
        return $this->chainEnd_deleteAll($query, $delete_type);
    }
    /**
     * idの指定の有無によりINSERT/UPDATE文の発行
     */
    public function chainEnd_save($query, $values=array())
    {
        if ($values) $query->addValues($values);
        $id_col_name = $query->getDef()->getIdColName();
        // IDが指定されていればUpdate
        if ($id = $query->getValue($id_col_name)) {
            $query->removeValue($id_col_name);
            return $this->chainEnd_updateById($query, $id);
        // IDの指定が無ければInsert
        } else {
            return $this->chainEnd_insert($query);
        }
    }

// -- トランザクションの操作

    /**
     * 指定したCallbackをトランザクション制御
     */
    public function chainEnd_transaction($query, $callback, $args=array())
    {
        app("events")->fire("table.transaction", array("begin"));
        $query->getDef()->getConnection()->transaction($callback, $args);
    }
    /**
     * ROLLBACKの発行
     * トランザクションの中断処理
     */
    public function chainEnd_rollback($query)
    {
        app("events")->fire("table.transaction", array("rollback"));
        $query->getDef()->getConnection()->rollback();
    }

// -- Result制御を組み合わせたSELECT文の発行

    /**
     * 特定カラムのみに絞った結果データを取得
     */
    public function chainEnd_selectHashedBy($query, $key_col_name, $key_col_name_sub=false, $col_name_sub_ex=false)
    {
        if ($key_col_name_sub === false) {
            return $query->makeBuilder()->fields(array($key_col_name))
                ->select()->getHashedBy($key_col_name);
        } elseif ($col_name_sub_ex === false) {
            return $query->makeBuilder()->fields(array($key_col_name, $key_col_name_sub))
                ->select()->getHashedBy($key_col_name, $key_col_name_sub);
        } else {
            return $query->makeBuilder()->fields(array($key_col_name, $key_col_name_sub, $col_name_sub_ex))
                ->select()->getHashedBy($key_col_name, $key_col_name_sub, $col_name_sub_ex);
        }
    }
    /**
     * 集計を行った結果データを取得
     */
    public function chainEnd_selectSummary($query, $summary_field, $key_col_name, $key_col_name_sub=false)
    {
        if ($key_col_name_sub === false) {
            return $query->makeBuilder()->fields(array("summary"=>$summary_field, $key_col_name))->groupBy($key_col_name)
                ->select()->getHashedBy($key_col_name, "summary");
        } else {
            return $query->makeBuilder()->fields(array(
                "summary" => $summary_field, $key_col_name, $key_col_name_sub))
                ->groupBy($key_col_name)->groupBy($key_col_name_sub)
                ->select()->getHashedBy($key_col_name, $key_col_name_sub, "summary");
        }
    }
}
