<?php
namespace R\Lib\Extention;

class CsvfilterLoader
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
        $enum = app()->enum($filter["enum"]);
        // CSV読み込み時
        if ($mode == "r") {
            $enum->initValues();
            $enum_reverse = array_flip((array)$enum);
            return $enum_reverse[$value];
        // CSV書き込み時
        } else {
            return $enum[$value];
        }
    }
}
