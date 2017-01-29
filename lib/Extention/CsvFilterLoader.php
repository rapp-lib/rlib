<?php
namespace R\Lib\Extention;

class CsvFilterLoader
{
    public static function getCallback ($name)
    {
        $class_name = get_class();
        $callback_method = "callback".str_camelize($name);
        if (method_exists($class_name,$callback_method)) {
            return array($class_name,$callback_method);
        }
    }
    // CSVからの入力項目についてのサニタイズ処理
    public static function callbackSanitize ($values, $mode, $line, $filter, $csv)
    {
        // オプションチェック
        if ($filter["target"]) {
            report_error('csvfilter:sanitize targetの指定は不可',array(
                "filter" =>$filter,
            ));
        }
        // CSV読み込み時
        if ($mode == "r") {
            $values =sanitize($values);
        // CSV書き込み時
        } elseif ($mode == "w") {
            foreach ($values as $k => $v) {
                $values[$k] =str_replace(array("&amp;","&lt;","&gt;"),array("&","<",">"),$v);
            }
        }
        return $values;
    }
    // 対象の値が正しい日付であるか評価、整形を行う
    public static function callbackDate ($value, $mode, $line, $filter, $csv)
    {
        // オプションチェック
        if ( ! $filter["target"]) {
            report_error('csvfilter:date targetの指定は必須です',array(
                "filter" =>$filter,
            ));
            return $value;
        }
        // 空白要素の無視
        if ( ! strlen($value)) {
            return $mode=="r" ? null : "";
        }
        // CSV読み込み時
        if ($mode == "r") {
            if (strtotime($value) == -1) {
                $csv->register_error("設定された値が不正です",true,$filter["target"]);
                return null;
            }
            if ($filter["format"]) {
                $value =longdate_format($value,$filter["format"]);
            }
        // CSV書き込み時
        } elseif ($mode == "w") {
            if ( ! longdate($value)) {
                return "";
            }
            if ($filter["format"]) {
                $value =longdate_format($value,$filter["format"]);
            }
        }
        return $value;
    }
    // 指定のlistでselect/select_reverseする
    public static function callbackListSelect ($value, $mode, $line, $filter, $csv)
    {
        // 空白要素の無視
        if ( ! $value || ($value && is_string($value) && ! strlen($value))) {
            return $mode=="r" ? null : "";
        }
        // listの指定
        if ($filter["list"]) {
            $list_options =get_list($filter["list"]);
            $list_params =(array)$filter["list_params"];
        }
        if ($filter["enum"]) {
            $enum = enum($filter["enum"],$list_params[0]);
            if ( ! isset($enum)) {
                report_error("csv_filterのenum指定が不正です", $filter);
            }
            if ($mode == "r") {
                $enum->initValues();
                $enum_reverse = array_flip((array)$enum);
            }
        }
        // target_parentの指定
        if ($target_parent =$filter["target_parent"]) {
            $list_params[] =$line[$target_parent];
        }
        // 複合データの場合
        if ($delim =$filter["delim"]) {
            // CSV読み込み時
            if ($mode == "r") {
                $value_exploded =explode($delim,$value);
                $value =array();
                foreach ($value_exploded as $k=>$v) {
                    if ($enum && $enum_reverse[$v]) {
                        $value_unserialized[$k] =$enum_reverse[$v];
                    } elseif ($v =$list_options->select_reverse($v, $list_params)) {
                        $value[$k] =$v;
                    } else {
                        $csv->register_error("設定された値が不正です",true,$filter["target"]);
                    }
                }
            // CSV書き込み時
            } elseif ($mode == "w") {
                $value_unserialized =array();
                $value =is_array($value)
                        ? $value
                        : (array)unserialize($value);
                foreach ($value as $k=>$v) {
                    if ($enum && $enum[$v]) {
                        $value_unserialized[$k] =$enum[$v];
                    } elseif ($list_options && $v =$list_options->select($v, $list_params)) {
                        $value_unserialized[$k] =$v;
                    } else {
                        $csv->register_error("設定された値が不正です",true,$filter["target"]);
                    }
                }
                $value =implode($delim,$value_unserialized);
            }
        // 単純データの場合
        } else {
            // CSV読み込み時
            if ($mode == "r") {
                if ($enum) {
                    $value =$enum_reverse[$value];
                } elseif ($list_options) {
                    $value =$select_reverse->select($value, $list_params);
                }
            // CSV書き込み時
            } elseif ($mode == "w") {
                if ($enum) {
                    $value =$enum[$value];
                } elseif ($list_options) {
                    $value =$list_options->select($value, $list_params);
                }
            }
            if ($value===null) {
                $csv->register_error("設定された値が不正です",true,$filter["target"]);
            }
        }
        return $value;
    }
}
