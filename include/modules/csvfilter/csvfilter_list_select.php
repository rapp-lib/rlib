<?php

    /*
        パラメータ：
            * target: 対象の項目
            * list: list名
            delim: 複合データもdelimで区切り指定すると展開可能
            list_params: select/select_reverseのparam指定
            target_parent: list_paramsに追加する親要素名の指定
    */
    //-------------------------------------
    // 指定のlistでselect/select_reverseする
    function csvfilter_list_select ($value, $mode, $line, $filter, $csv) {

        // 空白要素の無視
        if ( ! $value || ($value && is_string($value) && ! strlen($value))) {

            return $mode=="r" ? null : "";
        }

        // listの指定
        $list_options =get_list($filter["list"]);
        $list_params =(array)$filter["list_params"];
        if ($filter["enum"]) {
            $enum = enum($filter["enum"],$list_params[0]);
            if ($mode == "r") {
                $enum->initValues();
                $enum_reverse = array_reverse($enum);
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