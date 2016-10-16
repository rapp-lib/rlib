<?php

    /*
        パラメータ：
            指定なし
    */
    //-------------------------------------
    // パスワードハッシュに対する処理
    function csvfilter_password_hash ($values, $mode, $line, $filter, $csv) {

        // オプションチェック
        if ( ! $filter["target"]) {

            report_error('csvfilter:password_hash targetの指定は必須',array(
                "filter" =>$filter,
            ));
        }

        // CSV読み込み時
        if ($mode == "r") {

        // CSV書き込み時
        } elseif ($mode == "w") {

            $values ="";
        }

        return $values;
    }