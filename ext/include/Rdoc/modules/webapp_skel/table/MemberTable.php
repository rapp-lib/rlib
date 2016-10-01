<?php
    $table = $t["name"];
    $table_class = str_camelize($t["name"])."Table";
    $cols = $t["cols_all"];

    if ($cols[$t['pkey']]) {
        $cols[$t['pkey']]["def"]["id"] = true;
    }
    if ($cols[$t['del_flg']]) {
        $cols[$t['del_flg']]["def"]["del_flg"] = true;
    }

    $get_col_def = function ($col) {
        $col_name = $col["name"];
        $col_defs = $col["def"];
        $col_defs["comment"] = $col["label"];

        $defs = array();
        foreach ((array)$col_defs as $k => $v) {
            if (is_string($v)) {
                $v = '"'.$v.'"';
            } elseif (is_numeric($v)) {
                $v = $v;
            } elseif (is_null($v)) {
                $v = 'null';
            } elseif (is_bool($v)) {
                $v = $v ? 'true' : 'false';
            } else {
                $v = (string)$v;
            }
            $defs[] = '"'.$k.'"=>'.$v;
        }

        return str_repeat("    ",2).'"'.$col_name.'" => array('.implode(', ',$defs).'),'."\n";
    };

?><?="<!?php\n"?>
namespace R\App\Table;

/**
 * @table
 */
class <?=$table_class?> extends Table_App
{
    protected static $cols = array(
<?php foreach ((array)$cols as $col): ?>
<?=$get_col_def($col)?>
<?php endforeach; ?>
    );
    protected static $refs = array(
    );
    protected static $def = array(
        "table_name" => "<?=$table?>",
        "indexes" => array(),
    );
}