<?php
namespace R\Lib\Doc\Writer;

class SchemaCsvWriter
{
    protected $config = array(
        "tables_header"=>array(
            "#tables", "table", "col", "label", "def.type", "type",
            "def.notnull", "def.length", "def.default", "def.fkey_for",
            "def.assoc", "other",
        ),
        "controller_header"=>array(
            "#pages", "controller", "label", "type",
        ),
    );
    public function write ($filename, $schema)
    {
        $schema = $this->discompleteSchema($schema);
        $lines = $this->schemaToLines($schema);
        csv_open($filename, "w", array(
            "ignore_empty_line" => true,
        ))->writeLines($lines);
    }
    /**
     * スキーマの補完部分の削除
     */
    private function discompleteSchema ($schema)
    {
        // Controllerの補完
        if ( ! is_array($schema["controller"])) $schema["controller"] = array();
        foreach ($schema["controller"] as $name => & $c) {
            unset($c["name"]);
            if ($c["access_as"]=="guest") unset($c["access_as"]);
            if ( ! $c["priv_required"]) unset($c["priv_required"]);
        }
        // テーブルごとに処理
        if ( ! is_array($schema["tables"])) $schema["tables"] = array();
        foreach ($schema["tables"] as $t_name => & $t) {
            unset($t["name"]);
            // カラムごとに処理
            $cols = array();
            foreach ($t["cols"] as $tc_name => $tc) {
                if ($tc_name===$tc["def"]["name"]) unset($tc["def"]["name"]);
                $cols[$tc_name] = $tc;
            }
            $schema["cols"][$t_name] = $cols;
            unset($t["cols"]);
        }
        // tables_defに関する処理は実装しない
        return $schema;
    }
    /**
     * SchemaをCsv行に書き換え
     */
    private function schemaToLines ($schema)
    {
        $lines = array();
        // #tablesパート
        $header = $this->config["tables_header"];
        $lines[] = $header;
        foreach ($schema["tables"] as $table_name=>$table) {
            $table["table"] = $table_name;
            $lines[] = $this->flattenByHeader($table, $header);
            foreach ($schema["cols"][$table_name] as $col_name=>$col) {
                $col["col"] = $col_name;
                $lines[] = $this->flattenByHeader($col, $header);
            }
        }
        //TODO: #pagesパート
        return $lines;
    }
    /**
     * Headerに従って平坦化する
     */
    private function flattenByHeader ($data, $header)
    {
        $flat = array();
        $fheader = array_flip($header);
        foreach ($fheader as $k=>$i) {
            if ($k=="other") continue;
            $v = array_get($data, $k);
            array_unset($data, $k);
            $flat[$i] = $this->stringifyValue($v);
        }
        $flat[$fheader["other"]] = $this->stringifyValues($data);
        return $flat;
    }
    /**
     * 値を文字列表現に変換する
     */
    private function stringifyValue ($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        } elseif ($value===null || $value==="") {
            return "";
        } elseif ($value===true) {
            return "true";
        } elseif ($value===false) {
            return "false";
        } elseif (preg_match('!([\{\[\=\|]|^true$|^false$)!',$value)) {
            return '"'.$value.'"';
        } else {
            return $value;
        }
    }
    /**
     * 配列をパイプ文字列表現に変換する
     */
    private function stringifyValues ($values)
    {
        $r = array();
        foreach (array_dot($values) as $k=>$v) {
            $v = $this->stringifyValue($v);
            if (strlen($v)) $r []= $k."=".$v;
        }
        return implode(' | ', $r);
    }
}
