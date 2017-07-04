<?php
namespace R\Lib\Table;

class QB {
    public function render($q) {
        return call_user_func(array($this,'st'.str_camelize($q["type"])), $q);
    }
    // SQLステートメント基幹部
    protected function stSelect($q) {
        return "SELECT ".$this->stFields($q["fields"])." FROM ".$this->stTable($q["table"])
            .$this->stJoins($q["joins"]).$this->stWhere($q["where"]," WHERE ")
            .$this->stCols($q["group"], " GROUP BY ").$this->stWhere($q["having"], " HAVING ")
            .$this->stCols($q["order"], " ORDER BY ").$this->stLimitOffset($q["limit"], $q["offset"]);
    } protected function stUpdate($q) {
        return "UPDATE ".$this->stField($q["table"])
            ." SET ".$this->stSet($q["values"]).$this->stWhere($q["where"], "WHERE");
    } protected function stInsert($q) {
        return "INSERT ".$this->stField($q["table"])
            ." INTO ( ".$this->mapJoin($q["values"], function($self,$k,$v){ return $self->stKey($k); }, " , ")." )"
            ." VALUES ( ".$this->mapJoin($q["values"], function($self,$k,$v){ return $self->stValue($v); }, " , ")." )";
    } protected function stDelete($q) {
        return "DELETE ".$this->stField($q["table"]).$this->stWhere($q["where"], "WHERE");
    }
    // JOIN句
    protected function stJoins($xs) {
        return $this->mapJoin($xs, function($self,$k,$v){ return $self->stJoin($v[0], $v[1], $v[2]); });
    } protected function stJoin($table, $where, $type) {
        return ($type ?: " LEFT")." JOIN ".$this->stTable($table).$this->stWhere($where," ON ");
    }
    // WHERE句
    protected function stWhere($xs, $prefix=" WHERE ") {
        return ( ! $xs) ? "" : $prefix." (".$this->stCond($xs," AND ").") ";
    } protected function stCond($xs, $glue=" AND ") {
        return $this->mapJoin($xs, function($self,$k,$v){
            if (is_array($v) && ($k=="OR" || $k=="or")) return $self->stCond($v, " OR ");
            if (is_array($v) && is_numeric($k)) return $self->stCond($v, " AND ");
            if ( ! is_array($v) && is_numeric($k)) return $self->stKey($v);
            if (is_array($v) && ! is_numeric($k)) return $self->stKey($k)
                ." IN ( ".$self->mapJoin($v, function($self,$k,$v){ return $self->stValue($v); }, " , ")." )";
            return $self->stKey($k).(preg_match('!\s([<>=]=?|<>|\\!=|LIKE)$!',$k) ? "": " =").' '.$self->stValue($v);
        });
    }
    // 句固有の表現
    protected function stFields($xs) {
        return ( ! $xs) ? "*" : $this->mapJoin($xs, function($self,$k,$v){ return $self->stField($v); }, " , ");
    } protected function stCols($xs) {
        return $this->mapJoin($xs, function($self,$k,$v){ return $self->stField($v); }, " , ");
    } protected function stLimitOffset($limit, $offset) {
        return ($offset ? " OFFSET ".$offset : "").($limit ? " LIMIT ".$limit : "");
    } protected function stSet(array $xs) {
        return $this->mapJoin($xs, function($self,$k,$v){ return $self->stKey($k)."=".$self->stValue($v); }, " , ");
    }
    // 単独要素共通の表現
    protected function stField($x) {
        return is_array($x) ? $this->stName($x[0]).$this->stAlias($x[1]) : $this->stName($x);
    } protected function stTable($x) {
        return $this->stField($x);
    } protected function stName($k) {
        if (preg_match('!^\w+$!',$k)) return $this->stQuoteName($k);
        if (preg_match('!^(\w+)\.(\w+)$!',$k,$m)) return $this->stQuoteName($m[1]).".".$this->stQuoteName($m[2]);
        return $k;
    } protected function stAlias($k) {
        return ( ! $k) ? "" : "AS".$this->stQuoteName(k);
    } protected function stKey($k) {
        return $k;
    } protected function stValue($v) {
        if (is_numeric($v)) return $v;
        if (is_array($v)) return $this->mapJoin($v, function($self,$k,$v){ return $self->stValue($v); } );
        return "'".mysqli_real_escape_string($v)."'";
    } protected function stQuoteName($k) {
        return "`".$k."`";
    }
    // 共通処理
    protected function mapJoin($xs, $f, $glue="") {
        foreach ((array)$xs as $k=>$v) $xs[$k] = $f($this, $k,$v);
        return implode($glue, (array)$xs);
    }
}
