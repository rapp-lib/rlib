<?php
namespace R\Lib\DBAL;

class QB
{
    public static function render($q) {
        return call_user_func('\R\Lib\DBAL\QueryBuilder::st'.str_camelize($q["type"]), $q);
    }
    // SQLステートメント基幹部
    public static function stSelect($q) {
        return "SELECT ".QB::stFields($q["fields"])." FROM ".QB::stTable($q["table"])
            .QB::stJoins($q["joins"]).QB::stWhere($q["where"]," WHERE ")
            .QB::stCols($q["group_by"], " GROUP BY ").QB::stWhere($q["having"], " HAVING ")
            .QB::stCols($q["order_by"], " ORDER BY ").QB::stLimitOffset($q["limit"], $q["offset"]);
    } public static function stUpdate($q) {
        return "UPDATE ".QB::stField($q["table"])
            ." SET ".QB::stSet($q["values"]).QB::stWhere($q["where"], "WHERE");
    } public static function stInsert($q) {
        return "INSERT ".QB::stField($q["table"])
            ." INTO ( ".QB::mapJoin($q["values"], function($k,$v){ return QB::stKey($k); }, " , ")." )"
            ." VALUES ( ".QB::mapJoin($q["values"], function($k,$v){ return QB::stValue($v); }, " , ")." )";
    } public static function stDelete($q) {
        return "DELETE ".QB::stField($q["table"]).QB::stWhere($q["where"], "WHERE");
    }
    // JOIN句
    public static function stJoins($xs) {
        return QB::mapJoin($xs, function($k,$v){ return QB::stJoin($v[0], $v[1], $v[2]); });
    } public static function stJoin($table, $where, $type) {
        return ($type ?: " LEFT")." JOIN ".QB::stTable($table).QB::stWhere($where," ON ");
    }
    // WHERE句
    public static function stWhere($xs, $prefix="") {
        return ( ! $xs) ? "" : $prefix." (".QB::stCond($xs," AND ").") ";
    } public static function stCond($xs, $glue=" AND ") {
        return QB::mapJoin($xs, function($k,$v){
            if (is_array($v) && ($k=="OR" || $k=="or")) return QB::stCond($v, " OR ");
            if (is_array($v) && is_numeric($k)) return QB::stCond($v, " AND ");
            if ( ! is_array($v) && is_numeric($k)) return QB::stKey($v);
            if (is_array($v) && ! is_numeric($k)) return QB::stKey($k)
                ." IN ( ".QB::mapJoin($v, function($k,$v){ return QB::stValue($v); }, " , ")." )";
            return QB::stKey($k).(preg_match('!\s([<>=]=?|<>|\\!=|LIKE)$!',$k) ? "": " =").' '.QB::stValue($v);
        });
    }
    // 句固有の表現
    public static function stFields($xs) {
        return ( ! $xs) ? "*" : QB::mapJoin($xs, function($k,$v){ return QB::stField($v); }, " , ");
    } public static function stCols($xs) {
        return QB::mapJoin($xs, function($k,$v){ return QB::stField($v); }, " , ");
    } public static function stLimitOffset($limit, $offset) {
        return ($offset ? " OFFSET ".$offset : "").($limit ? " LIMIT ".$limit : "");
    } public static function stSet(array $xs) {
        return QB::mapJoin($xs, function($k,$v){ return QB::stKey($k)."=".QB::stValue($v); }, " , ");
    }
    // 単独要素共通の表現
    public static function stField($x) {
        return is_array($x) ? QB::stName($x[0]).QB::stAlias($x[1]) : QB::stName($x);
    } public static function stTable($x) {
        return QB::stField($x);
    } public static function stName($k) {
        if (preg_match('!^\w+$!',$k)) return QB::stQuote($k);
        if (preg_match('!^(\w+)\.(\w+)$!',$k,$m)) return QB::stQuote($m[1]).".".QB::stQuote($m[2]);
        return $k;
    } public static function stAlias($k) {
        return ( ! $k) ? "" : "AS".QB::stQuote(k);
    } public static function stKey($k) {
        return $k;
    } public static function stValue($v) {
        if (is_numeric($v)) return $v;
        if (is_array($v)) return QB::mapJoin($v, function($k,$v){ return QB::stValue($v); } );
        return "'".mysqli_real_escape_string($v)."'";
    } public static function stQuote($k) {
        return "`".$k."`";
    }
    // 共通処理
    public static function mapJoin($xs, $f, $glue="") {
        foreach ((array)$xs as $k=>$v) $xs[$k] = $f($k,$v);
        return implode($glue, (array)$xs);
    }
}
