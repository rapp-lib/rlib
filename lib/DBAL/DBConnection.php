<?php
namespace R\Lib\DBAL;

interface DBConnection
{
    public function __construct($dsname, $config);
    public function quoteName($name);
    public function quoteValue($value);
    public function getDbname();
    public function getDoctrineConnection();
    public function lastInsertId($table_name=null, $id_col_name=null);
// -- トランザクション制御
    public function begin();
    public function commit();
    public function rollback();
// -- SQL発行
    public function exec($st, $params=array());
    public function fetch($stmt);
}
