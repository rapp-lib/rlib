<?php
namespace R\Lib\Table\Query;

class Executer
{
    public function exec($query, $after_exec=null)
    {
        $result = $query->getDef()->getConnection()->transaction(function()use($query, $after_exec){
            // SQL文レンダリング
            $statement = $query->render();
            $start_ms = microtime(true);
            // SQL発行
            $result_res = $query->getDef()->getConnection()->exec($statement);
            // SQL発行イベント（Logging等）
            app("events")->fire("table.executed", array($statement, $result_res, $start_ms));
            // エラー制御
            if ( ! $result_res) {
                $error = $query->getDef()->getConnection()->getErrorInfo();
                report_error('SQL Error : '.implode(' , ',$error), array(
                    "Statement"=>"".$statement,
                    "Query"=>$statement->getQuery(),
                ));
            }
            // Resultの作成
            $result = app()->make("table.query_result", array($result_res, $statement));
            // SQL発行後処理
            if ($after_exec) call_user_func($after_exec, $result);
            app("table.features")->emit("after_exec", array($result));
            return $result;
        });
        return $result;
    }
    public function execFetchAll($query)
    {
        return $this->exec($query, function($result){
            // SQL発行後処理としてFetch
            $result_res = $result->getResultResource();
            $db = $result->getStatement()->getQuery()->getDef()->getConnection();
            while ($data = $db->fetch($result_res)) {
                // Recordの作成とFetch結果のHydrate
                $record = app()->make("table.query_record", array($result));
                $record->hydrate($data);
                $result[] = $record;
            }
            return $result;
        });
    }
}
