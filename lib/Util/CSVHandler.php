<?php
namespace R\Lib\Util;

/**
 * CSVファイル入出力を補助するクラス
 */
class CSVHandler
{
    protected $filename;
    protected $mode;
    protected $options;
    protected $handle;
    protected $current_line_num = 0;

    public function __construct ($filename, $mode="r", $options=array())
    {
        $this->filename = $filename;
        $this->mode = $mode;
        $this->options = $options;
        $this->handle = fopen($this->filename, $this->mode);
        // optionsの値設定
        $this->options["delim"] = $this->options["delim"] ?: ",";
        $this->options["escape"] = $this->options["escape"] ?: '"';
        $this->options["return_code"] = $this->options["return_code"] ?: "\n";
        $this->options["map"] = $this->options["map"] ?: null;
        $this->options["labels"] = $this->options["labels"] ?: null;
        if ($this->options["rows"]) {
            $this->options["map"] = array_keys($options["rows"]);
            $this->options["labels"] = $options["rows"];
        }
        $this->options["filters"] = $this->options["filters"] ?: array();
        if ($this->options["filters"]) ksort($this->options["filters"]);
        $this->options["data_charset"] = $this->options["data_charset"] ?: "UTF-8";
        $this->options["file_charset"] = $this->options["file_charset"] ?: "SJIS-WIN";
        $this->options["ignore_empty_line"] = (boolean)$this->options["ignore_empty_line"];
        $this->options["escape_all"] = (boolean)$this->options["escape_all"];
        // 標準filterの登録
        if ($this->options["data_charset"] != $this->options["file_charset"]) {
            array_unshift($this->options["filters"],
                array(null, array($this, "filterConvertCharset"), "ignore_skip"=>true));
        }
        // ラベル行の書き込み/スキップ
        if ($this->options["labels"]) {
            if ($this->mode=="r") $this->readLine(array("skip_filters"=>true));
            if ($this->mode=="w") $this->writeLine($this->options["labels"], array("skip_filters"=>true));
        }
    }
    /**
     * ファイルハンドルの取得
     */
    public function getHandle ()
    {
        return $this->handle;
    }
    /**
     * 現在の行番号を取得
     */
    public function getCurrentLineNum ()
    {
        return $this->current_line_num;
    }
    /**
     * 複数行読み込み
     */
    public function readLines ($options=array())
    {
        $lines = array();
        $counter = $options["limit"];
        while ( ! is_null($line = $this->readLine($options))) {
            if ( ! is_null($counter) && $counter-->0) break;
            $lines[] = $line;
        }
        return $lines;
    }
    /**
     * 1行読み込み
     */
    public function readLine ($options=array())
    {
        $csv_data = $this->readCsvLine();
        if ( ! isset($csv_data)) return;
        // 空行のスキップ
        if ($this->options["ignore_empty_line"] && ! strlen(implode("",$csv_data))) {
            return $this->readLine();
        }
        // KVマッピング
        if (is_array($this->options["map"])) {
            $csv_data_tmp = array();
            foreach ($this->options["map"] as $k => $v) $csv_data_tmp[$v] = $csv_data[$k];
            $csv_data = $csv_data_tmp;
        }
        // Filters実行
        if ($this->options["filters"]) $this->applyFilters($csv_data, "r", $options["skip_filters"]);
        // 配列ドット参照の解決
        if (is_array($this->options["map"])) {
            foreach (array_keys($csv_data) as $k) {
                if (strpos($k, ".")!==false) {
                    \R\Lib\Util\Arr::array_add($csv_data, $k, $csv_data[$k]);
                    unset($csv_data[$k]);
                }
            }
        }
        \R\Lib\Util\Arr::array_clean($csv_data);
        return $csv_data;
    }
    /**
     * 何も処理せずにCSVファイルを1行読み込む
     */
    private function readCsvLine ()
    {
        // ファイルの末尾に到達したらnullを返す
        if ( ! $this->handle || feof($this->handle)) return null;
        // エスケープを考慮して1行読み込み
        $d = $this->options["delim"];
        $e = $this->options["escape"];
        $r = $this->options["return_code"];
        $csv_line = "";
        $line = "";
        do {
            // \nで改行する場合はfgetsで読み込む
            if ($r==="\n") {
                $line .= fgets($this->handle);
            } else {
                do {
                    $line .= fread($this->handle,1);
                } while ( ! feof($this->handle) && ! preg_match('!'.$r.'$!', $line));
            }
            $item_count = preg_match_all('/'.$e.'/', $line, $dummy);
        } while ( ! feof($this->handle) && $item_count % 2 != 0);
        // \nで改行する場合は\r混在の曖昧さを許容する
        if ($r==="\n") {
            $csv_line = preg_replace('/(?:\r\n|[\r\n])?$/', $d, $line);
        } else {
            $csv_line = preg_replace('!(?:'.$r.')?$!', $d, $line);
        }
        $csv_pattern ='/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'
                .$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
        preg_match_all($csv_pattern, $csv_line, $matches);
        $csv_data = $matches[1];
        // エスケープの解除
        foreach ($csv_data as & $value) {
            $value = preg_replace('/^'.$e.'(.*)'.$e.'$/s','$1', $value);
            $value = str_replace($e.$e, $e, $value);
        }
        $this->current_line_num++;
        return $csv_data;
    }
    /**
     * 複数行書き込み
     */
    public function writeLines ($lines, $options=array())
    {
        foreach ((array)$lines as $line) $this->writeLine($line,$options);
    }
    /**
     * 1行書き込み
     */
    public function writeLine ($csv_data, $options=array())
    {
        $csv_data = (array)$csv_data;
        // 配列ドット参照の解決
        if (is_array($this->options["map"])) {
            // $csv_data["xxx.0.yyy"]に参照先の値をコピーする
            foreach ($this->options["map"] as $k => $v) {
                if ( ! isset($csv_data[$v])) $csv_data[$v] = \R\Lib\Util\Arr::array_get($csv_data, $v);
            }
            // コピー対象となった$csv_data["xxx"]を削除する
            foreach (array_keys($csv_data) as $k) {
                if ( ! in_array($k, $this->options["map"])) unset($csv_data[$k]);
            }
        }
        // Filters実行
        if ($this->options["filters"]) $this->applyFilters($csv_data, "w", $options["skip_filters"]);
        // VKマッピング
        if (is_array($this->options["map"])) {
            $csv_data_tmp = array();
            foreach ($this->options["map"] as $k => $v) {
                $csv_data_tmp[$k] = $csv_data[$v];
            }
            $csv_data = $csv_data_tmp;
            ksort($csv_data);
        }
        // 空行のスキップ
        if ($this->options["ignore_empty_line"] && ! strlen(implode("",$csv_data))) {
            return;
        }
        return $this->writeCsvLine($csv_data);
    }
    /**
     * 何も処理せずにCSVファイルに1行書き込む
     */
    private function writeCsvLine ($csv_data)
    {
        $d = $this->options["delim"];
        $e = $this->options["escape"];
        $r = $this->options["return_code"];
        foreach ($csv_data as & $value) {
            $value = str_replace($e,$e.$e, $value);
            $escape_pattern ='/['.$e.$d.$r.']/';
            if (preg_match($escape_pattern,$value) || $this->options["escape_all"]) {
                $value = $e.$value.$e;
            }
        }
        $line = implode($d, $csv_data).$r;
        fwrite($this->handle, $line);
        $this->current_line_num++;
    }
    /**
     * Filters適用
     * 読み込み時は正順、書き込み時は逆順に適用する
     */
    private function applyFilters ( & $csv_data, $mode, $skip_filters)
    {
        $filters = $mode=="r" ? $this->options["filters"] : array_reverse($this->options["filters"]);
        foreach ($filters as $filter) {
            if ($skip_filters && ! $filter["ignore_skip"]) continue;
            $filter["target"] = $filter["target"] ?: $filter[0];
            $filter["filter"] = $filter["filter"] ?: $filter[1];
            if ( ! $filter["target"]) $filter["target"] = array_keys($csv_data);
            if ( ! is_array($filter["target"])) $filter["target"] = array($filter["target"]);
            $module = $filter["filter"];

            if ( ! is_callable($module)) {
                $module = CSVFilterLoader::getCallback($filter["filter"]);
            }
            foreach ($filter["target"] as $target) {
                $csv_data[$target] = call_user_func($module, $csv_data[$target], $mode, $filter, $csv_data);
            }
        }
    }
    private function filterConvertCharset ($value, $mode, $filter, $csv_data)
    {
        // 文字コード変換処理
        // CSV読み込み時
        if ($mode == "r") {
            return mb_convert_encoding($value, $this->options["data_charset"], $this->options["file_charset"]);
        // CSV書き込み時
        } else {
            return mb_convert_encoding($value, $this->options["file_charset"], $this->options["data_charset"]);
        }
    }
}
class CSVFilterLoader
{
    public static function getCallback ($name)
    {
        $class_name = get_class();
        $callback_method = "callback".str_camelize($name);
        if (method_exists($class_name,$callback_method)) {
            return array($class_name,$callback_method);
        }
    }
    // 分解/結合
    public static function callbackExplode ($value, $mode, $filter, $csv_data)
    {
        $filter["delim"] = $filter["delim"] ?: ",";
        // CSV読み込み時
        if ($mode == "r") {
            return explode($filter["delim"], $value);
        // CSV書き込み時
        } else {
            return implode($filter["delim"], $value);
        }
    }
    // 指定のenumに変換
    public static function callbackEnumValue ($value, $mode, $filter, $csv_data)
    {
        // 配列であれば各要素を処理
        if (is_array($value)) {
            foreach ($value as & $v) {
                $v = self::callbackEnumValue($v, $mode, $filter, $csv_data);
            }
            return $value;
        }
        // 空白要素の無視
        if ( ! strlen($value)) return $value;
        // CSV読み込み時
        if ($mode == "r") {
            $enum_reverse = array();
            foreach (app()->enum[$filter["enum"]] as $k=>$v) $enum_reverse[$v] = $k;
            return $enum_reverse[$value];
        // CSV書き込み時
        } else {
            return app()->enum[$filter["enum"]][$value];
        }
    }
}
