<?php
namespace R\Lib\Table\Query;

class Result extends \ArrayObject
{
    protected $statement;
    protected $result_res;
    protected $last_insert_id;
    public function __construct($result_res, $statement)
    {
        $this->result_res = $result_res;
        $this->statement = $statement;
        // LastInsertIdの確保
        if ($statement->getQuery()->getType() === "insert") {
            $def = $statement->getQuery()->getDef();
            $id_col_name = $def->getIdColName();
            if ($def->getColAttr($id_col_name, "autoincrement")) {
                $this->last_insert_id = $def->getConnection()->lastInsertId($def, $id_col_name);
            } else {
                $this->last_insert_id = $statement->getQuery()->getValue($id_col_name);
            }
        } else {
            $this->last_insert_id = null;
        }
    }
    public function getStatement()
    {
        return $this->statement;
    }
    public function getResultResource()
    {
        return $this->result_res;
    }
    public function getLastInsertId()
    {
        return $this->last_insert_id;
    }
    public function __call($method_name, $args)
    {
        array_unshift($args, $this);
        return app("table.features")->call("result", $method_name, $args);
    }
}
