<?php
namespace R\Lib\Report;
use Monolog\Logger;

class ReportRenderer
{
    /**
     * 複数のRecordをまとめてテキストに変換
     */
    public static function renderAll($records, $format)
    {
        // 出力件数の制限
        $count = count($records);
        $limit = 100;
        if ($count > $limit) {
            $records_tmp = array();
            foreach (array_slice($records,0,$limit/2) as $record) $records_tmp[] = $record;
            $records_tmp[] = array(
                "message" => "Report表示件数が多すぎるので".($count-$limit)."/".$count."件中略しました",
                "level" => Logger::WARNING,
            );
            foreach (array_slice($records,-$limit/2) as $record) $records_tmp[] = $record;
            $records = $records_tmp;
        }
        if ($format=="array") {
            $res = array();
            foreach ($records as $record) $res []= self::render($record, $format);
            return $res;
        } else {
            $res = "";
            foreach ($records as $record) $res .= self::render($record, $format);
            return $res;
        }
    }
    /**
     * Recordをテキストに変換
     */
    public static function render($record, $format)
    {
        $message = $record['message'];
        $params = $record["context"];
        $level = $record["level"];
        $pos = $params["__"]["pos"];
        $bts = $params["__"]["bts"];
        $category = $params["__"]["category"];
        if ( ! $category && $level >= Logger::ERROR) $category = "Error";
        unset($params["__"]);
        // Array形式
        if ($format=="array") {
            $data = array();
            $data["id"] = substr(md5(mt_rand()),0,6);
            $data["category"] = $category;
            $data["message"] = $message;
            $data["params"] = $params;
            $data["pos"] = $pos;
            if ($level >= Logger::ERROR) {
                $data["color"] = "#ff0000";
            } elseif ($level >= Logger::WARNING) {
                $data["color"] = "#ffff00";
            } else {
                $data["color"] = "#00ff00";
            }
            if ($level >= Logger::ERROR || app()->config["app.debug"] >= 2) {
                $data["bts"] = $bts;
            }
            return $data;
        // HTML形式
        } elseif ($format=="html") {
            // 色の指定
            $c = "#00ff00";
            if ($level >= Logger::ERROR) $c ="#ff0000";
            elseif ($level >= Logger::WARNING) $c ="#ffff00";
            // DOM操作に使用する要素のID
            $elm_id ="ELM".sprintf('%07d',mt_rand(1,9999999));
            return '<div id="'.$elm_id.'" '
                .'onclick="var e=document.getElementById(\''.$elm_id.'\');'
                .'e.style.height =\'auto\'; e.style.cursor =\'auto\';" '
                .'ondblclick="var e=document.getElementById(\''.$elm_id.'_detail\');'
                .'e.style.display =\'block\'; e.style.cursor =\'auto\';" '
                .'style="font-size:14px;text-align:left;overflow:hidden;'
                .'margin:1px;padding:2px;font-family: monospace;'
                .'border:#888888 1px solid;background-color:'
                .'#000000;cursor:hand;line-height:20px;height:40px;color:'.$c.'">'.$pos
                .'<div style="margin:0 0 0 10px">'.$message.self::indentValues($params, $format).'</div>'
                .'<div style="margin:0 0 0 10px;display:none;" id="'.$elm_id.'_detail">'
                .'Backtraces '.self::indentValues($bts, $format).'</div></div>';
        // Console形式
        } elseif ($format=="console" || $format=="console_middle" || $format=="console_short") {
            $text = "";
            $text .= "# ".$message;
            if ($format=="console" || $format=="console_middle") {
                $text .= self::indentValues($params, $format);
            }
            if ($format=="console") {
                $text .= "\n[TRACE] ".self::indentValues($bts, $format);
            }
            // 色の指定
            // http://qiita.com/hidai@github/items/1704bf2926ab8b157a4f
            $cn = "\033[0m";
            $c = array("bg"=>"\033[30;42m", "fg"=>"\033[32;40m");
            if ($level >= Logger::ERROR) $c = array("bg"=>"\033[97;41m", "fg"=>"\033[31;40m");
            elseif ($level >= Logger::WARNING) $c = array("bg"=>"\033[30;43m", "fg"=>"\033[33;40m");
            return $c["bg"]."[".$category."] ".$pos.$cn."\n"
                .$c["fg"].$text.$cn."\n";
        }
    }
    /**
     * 値のHTML出力整形
     */
    private static function indentValues ($values, $format, $level=1)
    {
        $tab_code = $format==="html" ? "&nbsp;&nbsp;&nbsp;&nbsp;" : "    ";
        $br_code = $format==="html" ? "<br/>" : "\n";
        $text = "";
        if ($values["__type"]) {
            $text .= $values["__type"];
            unset($values["__type"]);
        }
        foreach ((array)$values as $k=>$v) {
            $text .= $br_code.str_repeat($tab_code, $level).$k." : ";
            if (is_array($v) && count($v)) {
                $text .= self::indentValues($v,$format,$level+1);
            } elseif (is_array($v) && ! count($v)) {
                $text .= "array[0]";
            } else {
                $text .= $format==="html" ? htmlspecialchars($v) : str_replace("\n",'\n',$v);
            }
        }
        return $text;
    }

// -- HandlableErrorの組み立て

    /**
     * 配列からHandlableErrorを組み立てる
     */
    public static function createHandlableError($values)
    {
        // message
        $message = (string)$values["message"];
        // message,level <- php_error_code,error_messsage
        if ($values['php_error_code']) {
            $message = '[PHP '.self::getPhpErrorCodeText($values['php_error_code']).'] '.$values['message'];
            $values["level"] = $values["level"] ?: self::getPhpErrorCodeLevel($values['php_error_code']);
        }
        // message,backtrace,file,line <- exception
        if ($e = $values["exception"]) {
            $message = "[PHP Uncaught ".get_class($e)."] ".$e->getMessage();
            $values["backtrace"] = $e->getTrace();
            $values['file'] = $e->getFile();
            $values['line'] = $e->getLine();
        }
        // params
        $params = (array)$values["params"];
        // params.bts <- backtrace
        if ( ! $values["backtrace"]) {
            $values["backtrace"] = debug_backtrace();
        }
        $params["__"]["bts"] = self::compactBacktraces($values["backtrace"]);
        // params.pos <- file,line,class,function or params.bts
        if ($values["file"]) {
            $params["__"]["pos"] = self::compactBacktraceRow(array(
                "file" => $values["file"],
                "line" => $values["line"],
                "class" => $values["class"],
                "function" => $values["function"],
            ));
        }
        // params.level <- level
        $params["level"] = $values["level"] ?: Logger::ERROR;
        return new HandlableError($message, $params);
    }
    /**
     * PHPのエラーコードをテキストに変換
     */
    private static function getPhpErrorCodeText($php_error_code)
    {
        $map = array(
            E_ERROR             => "E_ERROR",
            E_WARNING           => "E_WARNING",
            E_PARSE             => "E_PARSE",
            E_NOTICE            => "E_NOTICE",
            E_CORE_ERROR        => "E_CORE_ERROR",
            E_CORE_WARNING      => "E_CORE_WARNING",
            E_COMPILE_ERROR     => "E_COMPILE_ERROR",
            E_COMPILE_WARNING   => "E_COMPILE_WARNING",
            E_USER_ERROR        => "E_USER_ERROR",
            E_USER_WARNING      => "E_USER_WARNING",
            E_USER_NOTICE       => "E_USER_NOTICE",
            E_STRICT            => "E_STRICT",
            E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
            E_DEPRECATED        => "E_DEPRECATED",
            E_USER_DEPRECATED   => "E_USER_DEPRECATED",
        );
        return isset($map[$php_error_code]) ? $map[$php_error_code] : "UNKNOWN";
    }
    /**
     * PHPのエラーコードをLoggerInterfaceのエラーレベルに変換
     */
    private static function getPhpErrorCodeLevel($code)
    {
        $map = array(
            E_ERROR             => Logger::ERROR,
            E_WARNING           => Logger::WARNING,
            E_PARSE             => Logger::CRITICAL,
            E_NOTICE            => Logger::NOTICE,
            E_CORE_ERROR        => Logger::CRITICAL,
            E_CORE_WARNING      => Logger::WARNING,
            E_COMPILE_ERROR     => Logger::CRITICAL,
            E_COMPILE_WARNING   => Logger::WARNING,
            E_USER_ERROR        => Logger::ERROR,
            E_USER_WARNING      => Logger::WARNING,
            E_USER_NOTICE       => Logger::NOTICE,
            E_STRICT            => Logger::NOTICE,
            E_RECOVERABLE_ERROR => Logger::WARNING,
            E_DEPRECATED        => Logger::NOTICE,
            E_USER_DEPRECATED   => Logger::NOTICE,
        );
        return isset($map[$code]) ? $map[$code] : Logger::CRITICAL;
    }

// -- Recordの加工/補完

    /**
     * LoggerのRecord配列をRenderRecordに最適化
     */
    public static function compactRecord($record)
    {
        // btsの補完
        if ( ! $record["context"]["__"]["bts"]) {
            $record["context"]["__"]["bts"] = ReportRenderer::compactBacktraces(debug_backtrace());
        }
        // posの補完
        if ( ! $record["context"]["__"]["pos"]) {
            foreach ($record["context"]["__"]["bts"] as $bt) {
                if (preg_match('!^\(rapp\)/!',$bt)) {
                    $record["context"]["__"]["pos"] = $bt;
                }
            }
        }
        // __以外の値の整形
        foreach ($record["context"] as $k=>$v) {
            if ($k!=="__") {
                $record["context"][$k] = self::compactValue($v);
            }
        }
        return $record;
    }
    /**
     * 値を見やすい配列に変換
     */
    public static function compactValue($value, $level=0)
    {
        if ($level>20) return "*LEVEL_LIMIT_OVER*";
        $r = null;
        if (is_array($value) || is_object($value)) {
            if ($value instanceof \ArrayObject) {
                $r["__type"] = "object(".get_class($value).')['.count($value).']';
                $value = method_exists($value,"__report") ? (array)$value->__report() : $value->getArrayCopy();
            } elseif (is_object($value)) { 
                $r["__type"] = "object(".get_class($value).')';
                $value = method_exists($value,"__report") ? (array)$value->__report() : get_object_vars($value);
            }
            // 省略
            if (count($value) > ($limit=200)*2) {
                $value_copy = array();
                foreach(array_slice((array)$value, 0, $limit, true) as $k=>$v) $value_copy[$k] = $v;
                $value_copy[] = "... shortened into ".($limit*2)." / ".count($value)." ...";
                foreach(array_slice((array)$value, -$limit, $limit, true) as $k=>$v) $value_copy[$k] = $v;
                $value = $value_copy;
            }
            foreach ($value as $k=>$v) $r[$k] = self::compactValue($v, $level+1);
        /*
        if (is_array($value) || ($value instanceof \ArrayObject)) {
            if (is_object($value)) {
                $r["__type"] = "object(".get_class($value).')['.count($value).']';
            }
            foreach ($value as $k=>$v) {
                $r[$k] = self::compactValue($v);
            }
        } elseif (is_object($value)) {
            $r["__type"] = "object(".get_class($value).')';
            $obj_vars = method_exists($value,"__report") ? (array)$value->__report() : get_object_vars($value);
            foreach ($obj_vars as $k=>$v) {
                $r[$k] = self::compactValue($v);
            }
        */
        } elseif ( ! is_string($value) && is_callable($value)) {
            $ref = is_array($value) ? new \ReflectionMethod($value[0], $value[1]) : new \ReflectionFunction($value);
            $r = 'function '.$ref->getName().'@'.$ref->getFileName().'(L'.$ref->getStartLine().')';
        } elseif (is_null($value)) {
            $r = "null";
        } elseif (is_bool($value)) {
            $r = $value ? "true" : "false";
        } elseif ( ! is_string($value)) {
            $r = gettype($value)."(".$value.")";
        } else {
            $r = '"'.self::str_truncate($value,3000,'...').'"';
        }
        return $r;
    }

// -- Backtraceの加工

    /**
     * Backtraceを見やすい配列に変換
     */
    private static function compactBacktraces($bts)
    {
        $rs = array();
        $bts = array_reverse($bts);
        foreach ($bts as $i=>$bt) {
            // Monologの呼び出し以降は記録不要
            // if ($bt['class']=='Monolog\Logger') break;
            // 動的呼び出しでファイルの場所が解決できない場合Reflectionで解決
            if ( ! $bt["line"] && ($bts[$i-1]["function"]==="call_user_func"
                || $bts[$i-1]["function"]==="call_user_func_array")
                && (is_callable($cb = $bts[$i-1]["args"][0]))) {
                if (is_string($cb) && preg_match('!^(.*?)::(.*?)$!', $cb, $match)) $cb = array($match[1], $match[2]);
                try {
                    if (is_array($cb)) $ref = new \ReflectionMethod($cb[0], $cb[1]);
                    elseif (is_string($cb) || $cd instanceof \Closure) {
                        $ref =new \ReflectionFunction($cb);
                    } elseif (is_object($cb) && method_exists($cb, "__invoke")) {
                        $ref = new \ReflectionMethod($cb, "__invoke");
                    } else {
                        report_error("解析不能");
                    }
                    $bt["file"] = $ref->getFileName();
                    $bt["line"] = $ref->getStartLine();
                } catch (\Exception $e) {
                }
            }
            $r = self::compactBacktraceRow($bt);
            // vendor内は記録不要
            if (preg_match('!^\(vendor\)/!',$r)) continue;
            $rs[] = $r;
        }
        return $rs;
    }
    /**
     * Backtrace1行を見やすい配列に変換
     */
    private static function compactBacktraceRow($bt)
    {
        $lib_path = realpath(dirname(__FILE__)."/../..");
        $vendor_path = realpath($lib_path."/../..");
        $logger_path = realpath($vendor_path."/monolog/monolog");
        $laravel_path = realpath($vendor_path."/laravel/framework");
        $app_path = realpath($vendor_path."/..");
        $r = "";
        $bt['file'] = realpath($bt['file']);
        if (is_dir($bt['file'])) {
            $r .= "(dir)";
        } elseif (strpos($bt['file'],$lib_path)===0) {
            $r .= "(rlib)".mb_substr($bt['file'],mb_strlen($lib_path));
        } elseif (strpos($bt['file'],$laravel_path)===0) {
            $r .= "(laravel)".mb_substr($bt['file'],mb_strlen($laravel_path));
        } elseif (strpos($bt['file'],$logger_path)===0) {
            $r .= "(logger)".mb_substr($bt['file'],mb_strlen($logger_path));
        } elseif (strpos($bt['file'],$vendor_path)===0) {
            $r .= "(vendor)".mb_substr($bt['file'],mb_strlen($vendor_path));
        } elseif (strpos($bt['file'],$app_path)===0) {
            $r .= "(rapp)".mb_substr($bt['file'],mb_strlen($app_path));
        } else {
            $r .= "(ext)".$bt['file'];
        }
        if ($bt["line"]) {
            $r .= "(L".$bt['line'].") ";
        }
        $r .= ' - ';
        if ($bt["class"]) {
            $r .= $bt['class'].$bt['type'];
        }
        if ($bt["function"]) {
            $r .= $bt['function'];
        }
        if ($bt["args"]) {
            $r .= "(";
            foreach ($bt["args"] as $i=>$arg) {
                if ($i>0) {
                    $r .=" , ";
                }
                if (is_object($arg)) {
                    $r .= get_class($arg);
                } elseif (is_array($arg)) {
                    $r .= "array[".count($arg)."]";
                } elseif (strlen($arg) > 1000) {
                    $r = '"...'.strlen($arg).'bytes..."';
                } else {
                    $r .= '"'.self::str_truncate((string)$arg,20,'...').'"';
                }
            }
            $r .= ")";
        }
        return $r;
    }
    /**
     * 文字数指定して省略
     */
    private static function str_truncate($str, $length, $postfix="...")
    {
        return mb_strlen($str,"UTF-8")>$length ? mb_substr($str,0,$length,"UTF-8").$postfix : $str;
    }
}
