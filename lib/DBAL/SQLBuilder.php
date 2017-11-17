<?php
namespace R\Lib\DBAL;

class SQLBuilder
{
    public $config;
    public function __construct($config)
    {
        $this->config = $config;
    }
    public function render($q)
    {
        return call_user_func(array($this,'st'.str_camelize($q["type"])), $q);
    }
// -- 文基幹部
    public function stSelect($q)
    {
        return "SELECT ".$this->stSelectFields($q["fields"])
            ." FROM ".$this->stTable($q["table"])
            .$this->stJoins($q["joins"])
            .$this->stWhere($q["where"]," WHERE ")
            .$this->stCols($q["group"], " GROUP BY ")
            .$this->stWhere($q["having"], " HAVING ")
            .$this->stCols($q["order"], " ORDER BY ")
            .$this->stLimitOffset($q["limit"], $q["offset"]);
    }
    public function stUpdate($q)
    {
        return "UPDATE ".$this->stTable($q["table"])
            ." SET ".$this->stSet($q["values"])
            .$this->stWhere($q["where"], " WHERE ");
    }
    public function stInsert($q)
    {
        return "INSERT INTO ".$this->stTable($q["table"])
            ." ( ".$this->mapJoin($q["values"], function($self,$k,$v){ return $self->stName($k); }, " , ")." )"
            ." VALUES ( ".$this->mapJoin($q["values"], function($self,$k,$v){ return $self->stValue($v); }, " , ")." )";
    }
    public function stDelete($q)
    {
        return "DELETE ".$this->stTable($q["table"])
            .$this->stWhere($q["where"], "WHERE");
    }
// -- JOIN句
    public function stJoins($xs)
    {
        return $this->mapJoin($xs, function($self,$k,$v){ return $self->stJoin($v[0], $v[1], $v[2]); });
    }
    public function stJoin($table, $where, $type)
    {
        return " ".($type ?: "LEFT")." JOIN ".$this->stTable($table).$this->stWhere($where," ON ");
    }
// -- WHERE句
    public function stWhere($xs, $prefix=" WHERE ")
    {
        return ( ! $xs) ? "" : $prefix." ".$this->stCondList($xs," AND ");
    }
    public function stCondList($xs, $glue=" AND ")
    {
        if (is_array($xs)) $xs = $this->filter($xs, function($self,$k,$v){ return ! (is_array($v) && count($v)==0); });
        if ( ! is_array($xs)) return $this->stCondItem(0, $xs);
        if (count($xs)==1) return $this->mapJoin($xs, function($self,$k,$v){ return $self->stCondItem($k, $v); });
        return "(".$this->mapJoin($xs, function($self,$k,$v){ return $self->stCondItem($k, $v); }, $glue).")";
    }
    public function stCondItem($k, $v)
    {
        if (is_string($v) && is_numeric($k)) return $v;
        if (is_array($v) && preg_match('!^AND$!i',$k) || is_numeric($k)) return $this->stCondList($v, " AND ");
        if (is_array($v) && preg_match('!^OR$!i',$k)) return $this->stCondList($v, " OR ");
        if (is_array($v) && preg_match('!^NOT$!i',$k)) return " NOT ".$this->stCondList($v, " AND ");
        if ($p = $this->parseCritOp($k)) return $this->stKvCrit($p[0], $p[1], $v);
        return $this->stKvCrit($k, "=", $v);
    }
    public function stKvCrit($k, $op, $v)
    {
        if (is_array($v) && count($v)==0) $v = null;
        if (is_array($v) && count($v)==1) $v = array_shift($v);
        if (is_array($v) && ($op=="=" || $op=="==")) $op = "IN";
        if (is_array($v) && ($op=="<>" || $op=="!=")) $op = "NOT IN";
        if (is_array($v)) return $this->stName($k)." ".$op." "."( ".$this->mapJoin($v, function($self,$i,$v){
            return $self->stValue($v);
        }, " , ")." )";
        if ($v===null && ($op=="=" || $op=="==")) return $this->stName($k)." IS NULL";
        if ($v===null && ($op=="<>" || $op=="!=")) return $this->stName($k)." IS NOT NULL";
        return $this->stName($k)." ".$op." ".$this->stValue($v);
    }
// -- SELECT文固有の表現
    public function stSelectFields($xs)
    {
        return ( ! $xs) ? "*" : $this->mapJoin($xs, function($self,$k,$v){ return $self->stField($v); }, " , ");
    }
    public function stCols($xs, $prefix)
    {
        return ( ! $xs) ? "" : $prefix.$this->mapJoin($xs, function($self,$k,$v){ return $self->stExpression($v); }, " , ");
    }
    public function stLimitOffset($limit, $offset)
    {
        return ($offset ? " OFFSET ".$offset : "").($limit ? " LIMIT ".$limit : "");
    }
// -- UPDATE文固有の表現
    public function stSet(array $xs)
    {
        return $this->mapJoin($xs, function($self,$k,$v){ return $self->stName($k)." = ".$self->stValue($v); }, " , ");
    }
// -- 共通の表現
    public function stTable($x)
    {
        return is_array($x) ? $this->stExpression($x[0]).$this->stAlias($x[1]) : $this->stExpression($x);
    }
    public function stField($x)
    {
        return is_array($x) ? $this->stExpression($x[0]).$this->stAlias($x[1]) : $this->stExpression($x);
    }
    public function stAlias($k)
    {
        return ( ! $k) ? "" : " AS ".$this->stName($k);
    }
// -- Primitiveの表現
    public function stExpression($k)
    {
        if (preg_match('!^[\w\d_\.]+$!',$k)) return $this->stName($k);
        return $k;
    }
    public function stName($k)
    {
        return $this->mapJoin(preg_match('!^[\w\d_\.]+$!',$k) ? explode(".", $k) : array($k), function($self,$k,$v){
            return call_user_func($self->config["quote_name"], $v);
        }, ".");
    }
    public function stValue($v)
    {
        if ($v === null || (is_array($v) && empty($v))) return 'NULL';
        if (is_int($v) || is_float($v) || $v === '0') return $v;
        return call_user_func($this->config["quote_value"], $v);
    }
// -- その他
    public function mapJoin($xs, $f, $glue="")
    {
        // 各要素に処理を行ってから文字列連結
        foreach ((array)$xs as $k=>$v) $xs[$k] = $f($this, $k, $v);
        return implode($glue, (array)$xs);
    }
    public function filter($xs, $f)
    {
        // 要素のフィルタリング
        foreach ((array)$xs as $k=>$v) if ( ! $f($this, $k, $v)) unset($xs[$k]);
        return $xs;
    }
    public function parseCritOp($k)
    {
        // 条件式の演算子の分解
        $ops = array("<",">","=","<=",">=","==","<>","!=","LIKE","IN","NOT LIKE","NOT IN");
        $ptn = $this->mapJoin($ops, function($self,$k,$v){ return preg_quote($v,'!'); }, "|");
        return preg_match('!^(.+?)\s+?('.$ptn.')$!i',$k,$_) ? array($_[1], $_[2]) : false;
    }
}
