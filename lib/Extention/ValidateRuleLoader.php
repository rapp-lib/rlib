<?php
namespace R\Lib\Extention;

class ValidateRuleLoader
{
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

    private static $regacy_callbacks = array();
    public static function getCallback ($name)
    {
        $class_name = get_class();
        $callback_method = "callback".str_camelize($name);
        if (method_exists($class_name,$callback_method)) {
            return array($class_name,$callback_method);
        }

        // 旧仕様クラスの読み込み
        if (self::$regacy_callbacks[$name]) {
            return self::$regacy_callbacks[$name];
        }
        $class_name = "R\\Lib\\Form\\Rule\\".str_camelize($name);
        if (class_exists($class_name)) {
            return self::$regacy_callbacks[$name] = function ($validator, $value, $rule) use ($class_name) {
                $rule =new $class_name($rule);
                $result = $rule->check($name);
                return $result ? false : array("message"=>$rule->getMessage());
            };
        }
    }

    /**
     * 必須チェック
     */
    public static function callbackRequired ($validator, $value, $rule)
    {
        if (strlen($value)) {
            return false;
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
     *  id_col_name : IDカラム名
     */
    public static function callbackDuplecate ($validator, $value, $rule)
    {
        if (strlen($value)) {
            return false;
        }
        $rule["table"];
        $rule["col_name"];
        $rule["id_col_name"];
        return array("message"=>"既に登録されています");
    }
    /**
     * 同一入力チェック
     *  target : （必須）対象のField名
     */
    public static function callbackConfirm ($validator, $value, $rule)
    {
        if (strlen($value)) {
            return false;
        }
        $target_value = $validator->getValue($rule["target"]);
        if ($target_value==$value) {
            return false;
        }
        return array("message"=>"入力された値が異なっています");
    }
}