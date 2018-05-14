<?php
namespace R\Lib\Doc\Content;
use R\Lib\Builder\SchemaCsvLoader;
use R\Lib\Doc\Writer\SchemaCsvWriter;

class SchemaCsv extends Content_Base
{
    protected $header = array(
        "tables"=>array(
            "#tables", "table", "col", "label", "def.type", "type",
            "def.notnull", "def.length", "def.default", "def.fkey_for",
            "def.assoc", "other",
        ),
        "pages"=>array(
            "#pages", "controller", "label", "type",
        ),
    );
    public function setHeader($header)
    {
        return $this->header;
    }
    public function loadFrom($filename)
    {
        $loader = new SchemaCsvLoader();
        $this->data = $loader->load($filename);
    }

// --

    public function exportCsv()
    {
        $schema = $this->discompleteSchema($this->data);
        $lines = $this->schemaToLines($schema);
        return $lines;
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
        $header = $this->header["tables"];
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
            $v = \R\Lib\Util\Arr::array_get($data, $k);
            \R\Lib\Util\Arr::array_unset($data, $k);
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
        foreach (\R\Lib\Util\Arr::array_dot($values) as $k=>$v) {
            $v = $this->stringifyValue($v);
            if (strlen($v)) $r []= $k."=".$v;
        }
        return implode(' | ', $r);
    }
}
