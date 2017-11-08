<?php
namespace R\Lib\Form;

class ValidateRuleLoader
{
    public static function getCallback ($name)
    {
        if ((is_array($name) || preg_match('!::!', $name)) && is_callable($name)) return $name;
        $class_name = get_class();
        $callback_method = "callback".str_camelize($name);
        if (method_exists($class_name,$callback_method)) return array($class_name,$callback_method);
    }

// -- 入力チェックの定義

    /**
     * 形式チェックリスト
     */
    private static $format_enable =array(
        "regex" => array(null, "正しい形式で入力してください"),
        "mail" => array('!^([a-z0-9_]|\-|\.|\+)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$!', "正しいメールアドレスを入力してください"),
        "tel" => array('!^\d[\d-]+\d$!', "半角数字(ハイフンあり可)で入力してください"),
        "zip" => array('!^\d\d\d-?\d\d\d\d\d?$!', "半角数字(ハイフンあり可)で入力してください"),
        "alphabet" => array('!^[a-zA-Z]+$!', "半角英字のみで入力してください"),
        "number" => array('!^\d+$!', "半角数字のみで入力してください"),
        "alphanum" => array('!^[a-zA-Z0-9]+$!', "半角英数字のみで入力してください"),
        "kana" => array('!^(ア|イ|ウ|エ|オ|カ|キ|ク|ケ|コ|サ|シ|ス|セ|ソ|タ|チ|ツ|テ|ト|ナ|ニ|ヌ|ネ|ノ|ハ|ヒ|フ|ヘ|ホ|マ|ミ|ム|メ|モ|ヤ|ユ|ヨ|ラ|リ|ル|レ|ロ|ワ|ヲ|ン|ァ|ィ|ゥ|ェ|ォ|ッ|ャ|ュ|ョ|ー|ガ|ギ|グ|ゲ|ゴ|ザ|ジ|ズ|ゼ|ゾ|ダ|ヂ|ヅ|デ|ド|バ|ビ|ブ|ベ|ボ|パ|ピ|プ|ペ|ポ|ヴ| |　|・)*$!u', "全角カナのみで入力してください"),
        "date" => array('!^(\d+)[/-]+(\d+)[/-](\d+)$!', "正しい日付を入力してください"),
    );
    /**
     * 必須チェック
     */
    public static function callbackRequired ($validator, $value, $rule)
    {
        if (is_array($value)) {
            if (count($value) > 0) {
                return false;
            }
            return false;
        } else {
            if (strlen($value)) {
                return false;
            }
        }
        return array("message"=>"必ず入力して下さい");
    }
    /**
     * 形式チェック
     *  format : （必須）形式の名前
     *  regex : 正規表現
     */
    public static function callbackFormat ($validator, $value, $rule)
    {
        if ( ! strlen($value)) {
            return false;
        }
        // パラメータチェック
        if ( ! isset(self::$format_enable[$rule["format"]])) {
            report_error("パラメータの指定が不正です",array(
                "rule" => $rule,
                "format_enable" => array_keys(self::$format_enable),
            ));
        }
        list($regex, $message) = self::$format_enable[$rule["format"]];
        // regexの指定があれば正規表現を上書き
        if ($rule["regex"]) {
            $regex = $rule["regex"];
        }
        if (preg_match($regex,$value)) {
            return false;
        }
        return array("message"=>$message);
    }
    /**
     * 範囲チェック
     *  min : 最小値
     *  max : 最大値
     */
    public static function callbackRange ($validator, $value, $rule)
    {
        if ( ! strlen($value)) {
            return false;
        }
        $min = $rule["min"];
        $max = $rule["max"];
        if ($rule["date"]) {
            if (isset($min)) { $min = strtotime($min); }
            if (isset($max)) { $max = strtotime($max); }
            if (isset($value)) { $value = strtotime($value); }
        }
        if (isset($min) && isset($max)) {
            if ($min > $value || $max < $value) {
                return array("message"=>"正しい値を入力して下さい");
            }
        } elseif (isset($min)) {
            if ($min > $value) {
                return array("message"=>"正しい値を入力して下さい");
            }
        } elseif (isset($max)) {
            if ($max < $value) {
                return array("message"=>"正しい値を入力して下さい");
            }
        }
        return false;
    }
    /**
     * 文字列長チェック
     *  min : 最小値
     *  max : 最大値
     */
    public static function callbackLength ($validator, $value, $rule)
    {
        if ( ! strlen($value)) {
            return false;
        }
        $min = $rule["min"];
        $max = $rule["max"];
        $length =mb_strlen(str_replace("\r\n", "\n", $value),"UTF-8");
        if (isset($min) && isset($max)) {
            if ($min==$max && $min!=$kength) {
                return array("message"=>$min."文字で入力してください");
            } elseif ($min > $length || $max < $length) {
                return array("message"=>$min."文字以上".$max."文字以内で入力してください");
            }
        } elseif (isset($min)) {
            if ($min > $length) {
                return array("message"=>$min."文字以上で入力してください");
            }
        } elseif (isset($max)) {
            if ($max < $length) {
                return array("message"=>$max."文字以内で入力してください");
            }
        }
        return false;
    }
    /**
     * 重複チェック
     *  table : （必須）テーブル名
     *  col_name : （必須）カラム名
     *  id_field_name : IDフィールド名
     */
    public static function callbackDuplicate ($validator, $value, $rule)
    {
        if ( ! strlen($value))  return false;
        $q = table($rule["table"]);
        $q = $q->findBy($rule["col_name"], $value);
        if ($rule["id_field"]) $q = $q->findBy($q->getQueryTableName().".".$q->getIdColName()." <>", $validator->getValue($rule["id_field"]));
        if (count($q->select())==0) return false;
        return array("message"=>"既に登録されています");
    }
    /**
     * 同一入力チェック
     *  target : （必須）対象のField名
     */
    public static function callbackConfirm ($validator, $value, $rule)
    {
        if ( ! strlen($value)) return false;
        $target_value = $validator->getValue($rule["target_field"]);
        if ($target_value==$value) return false;
        return array("message"=>"入力された値が異なっています");
    }
    /**
     * ファイルアップロードのエラーチェック
     */
    public static function callbackValidFile ($validator, $value, $rule)
    {
        if (is_string($value) && ! strlen($value)) {
            return false;
        }
        if ( ! $value instanceof \Psr\Http\Message\UploadedFileInterface) {
            return false;
        }
        $error = $value->getError();
        // 値: 4; ファイルはアップロードされませんでした。
        if ($error  == UPLOAD_ERR_NO_FILE) {
            $result = false;
        // 値: 0; エラーはなく、ファイルアップロードは成功しています。
        } elseif ($error == UPLOAD_ERR_OK) {
            $result = false;
        // 値: 3; アップロードされたファイルは一部のみしかアップロードされていません。
        } elseif ($error == UPLOAD_ERR_PARTIAL) {
            $result = array("message"=>"ファイルのアップロードが完了しませんでした");
        // 値: 1; アップロードされたファイルは、php.ini の upload_max_filesize ディレクティブの値を超えています。
        // 値: 2; アップロードされたファイルは、HTML フォームで指定された MAX_FILE_SIZE を超えています。
        } elseif ($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE) {
            $result = array("message"=>"ファイルサイズが制限容量オーバーです");
        // 値: 6; テンポラリフォルダがありません。
        // 値: 7; ディスクへの書き込みに失敗しました。
        // 値: 8; PHP の拡張モジュールがファイルのアップロードを中止しました。
        } elseif ($error > 4) {
            $result = array("message"=>"ファイルが正しくアップロードできませんでした");
        } else {
            report_warning("ファイルアップロードで原因不明のエラーがありました",array(
                "value" => $value,
                "rule" => $rule,
            ));
        }
        if ($result) {
            report_warning("ファイルアップロードで問題がありました",array(
                "error" => $result,
                "value" => $value,
                "rule" => $rule,
            ));
        }
        return $result;
    }
}
