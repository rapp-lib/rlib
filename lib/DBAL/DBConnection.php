<?php
namespace R\Lib\DBAL;

interface DBConnection
{
    public function __construct($dsname, $config);
    public function getDbname();
    public function quoteName($name);
    public function quoteValue($value);
// -- SQL発行
    public function exec($st, $params=array());
// -- 結果の取得
    public function fetch($exec_return);
    public function fetchAll($exec_return);
    public function lastInsertId($table_name=null, $id_col_name=null);
// -- トランザクション制御
    public function begin();
    public function commit();
    public function rollback();
}
