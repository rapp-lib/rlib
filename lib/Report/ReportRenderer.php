<?php
namespace R\Lib\Report;
use Monolog\Logger;

class ReportRenderer
{
    public function __render ($record)
    {
        // 旧ドライバIF変換
        $errstr = $record['message'];
        $params = $record["context"];
        $options = $record;
        // 旧report互換処理
        if ($options["level"] >= Logger::ERROR) {
            $options["errno"] = E_USER_ERROR;
        } else if ($options["level"] >= Logger::WARNING) {
            $options["errno"] = E_USER_WARNING;
        } else {
            $options["errno"] = E_USER_NOTICE;
        }
        if ( ! $options["errfile"] && $params["__"]["file"]) {
            $options["errfile"] = $params["__"]["file"];
            $options["errline"] = $params["__"]["line"];
        }
        if ( ! $options["backtraces"] && $params["__"]["backtraces"]) {
            $options["backtraces"] = $params["__"]["backtraces"];
        }
        $backtraces = $options["backtraces"] ?: debug_backtrace();
        unset($params["__"]);

        $config =array();
        $config["output_format"] = ! (php_sapi_name()=="cli") ? "html" : "plain";
        $html = self::report_template($errstr,$params,$options,$backtraces,$config);
        return $html;
    }
    private function report_template ($errstr, $params, $options, $backtraces, $config)
    {
        $libpath = realpath(dirname(__FILE__)."/../..");
        $vendor_path = realpath($libpath."/../..");
        $app_path = realpath($vendor_path."/../../..");
        $report_filepaths = array(
            $libpath."/lib/Logger/LoggerDriver.php",
            $libpath."/lib/Error/ErrorDriver.php",
            $libpath."/functions/report_functions.php",
        );
        $errdetail =array();
        $errset =false;
        $errfile ="";
        $errline ="";
        $errpos ="";
        if ($options["errfile"]) {
            $errset =true;
            $errfile ='';
            $errfile .=strstr($options['errfile'],$libpath)!==false ? "rlib/" : "";
            $errfile .=basename($options['errfile']);
            $errline =$options["errline"];
        }
        // backtraceの選択
        for ($i=0; $i < count($backtraces); $i++) {
            $backtrace =$backtraces[$i];
            $backtrace['file'] =realpath($backtrace['file']);
            if ($backtrace['class']=='Monolog\Logger') {
                $errdetail = array();
            }
            if (in_array($backtrace['file'],$report_filepaths)) {
                $errdetail = array();
            }
            $errdetail[$i] = ReportRenderer::compactBacktraceRow($backtrace);
            // $errdetail[$i] .=strstr($backtrace['file'],$libpath)!==false ? "rlib/" : "";
            // $errdetail[$i] .=basename($backtrace['file']);
            // $errdetail[$i] .=($backtrace['line'] ? "(L".$backtrace['line'].") " : "");
            // $errdetail[$i] .=' - ';
            // $errdetail[$i] .=$backtrace['class'] ? $backtrace['class'].$backtrace['type'] : "";
            // $errdetail[$i] .=$backtrace['function'] ? $backtrace['function'] : "";
            if ( ! $errset) {
                $errfile ='';
                $errfile .=strstr($backtrace['file'],$libpath)!==false ? "rlib/" : "";
                $errfile .=basename($backtrace['file']);
                $errline =$backtrace['line'];
                $errpos ="";
                $errpos .=$backtrace['class'] ? $backtrace['class'].$backtrace['type'] : "";
                $errpos .=$backtrace['function'] ? $backtrace['function'] : "";
            }
            if (strlen($backtrace['line']) && strstr($backtrace['file'],$vendor_path)===false) {
                $errset =true;
            }
        }
        $elm_id ="ELM".sprintf('%07d',mt_rand(1,9999999));
        // レポートの整形と出力
        $report_html ="";
        // HTML形式
        if ($config["output_format"]=="html") {
            $font_color ="#00ff00";
            $elm_class ="";
            $message ="";
            if ($options["errno"] & E_USER_NOTICE) {
                $font_color ="#00ff00";
                $elm_class ="notice";
            } elseif ($options["errno"] & (E_USER_ERROR | E_ERROR)) {
                $font_color ="#ff0000";
                $elm_class ="warning";
            } else {
                $font_color ="#ffff00";
                $elm_class ="error";
            }
            if (is_array($params) && is_string($errstr)) {
                $message .=$errstr;
            } else {
                $message .=self::decorate_value($errstr,true);
            }
            if ($params!==null) {
                $message .=self::decorate_value($params,true);
            }
            // if (registry("Report.report_backtraces")) {
            //     $message .='<br/> [BACKTRACES] :'.self::decorate_value($backtraces,true);
            // }
            $report_html .='<div class="ruiReport '.$elm_class.'" id="'.$elm_id.'" '
                    .'onclick="var e=document.getElementById(\''.$elm_id.'\');'
                    .'e.style.height =\'auto\'; e.style.cursor =\'auto\';" '
                    .'ondblclick="var e=document.getElementById(\''.$elm_id.'_detail\');'
                    .'e.style.display =\'block\'; e.style.cursor =\'auto\';" '
                    .'style="font-size:14px;text-align:left;overflow:hidden;'
                    .'margin:1px;padding:2px;font-family: monospace;'
                    .'border:#888888 1px solid;background-color:'
                    .'#000000;cursor:hand;height:40px;color:'.$font_color.'">'
                    .$errfile.($errline ? '(L'.$errline.')' : "").' - '.$errpos
                    .'<div style="margin:0 0 0 10px">'
                    .$message.'</div>'
                    .'<div style="margin:0 0 0 10px;display:none;" id="'.$elm_id.'_detail">'
                    .'Backtrace: '.self::decorate_value(array_reverse($errdetail),true).'</div></div>';
        // 非HTML形式
        } elseif ( ! $html_mode) {
            if (is_string($errstr)) {
                $report_html .= "*\n* ".$errstr."\n*";
            } else {
                $report_html .= "[VALUE] ".self::decorate_value($errstr,false);
            }
            if (is_array($params)) {
                $report_html .= "\n[PARAM] ".self::decorate_value($params,false);
            }
            if (is_array($errdetail)) {
                $report_html .="\n".'[TRACE] '.self::decorate_value(array_reverse($errdetail),false);
            }
            // http://qiita.com/hidai@github/items/1704bf2926ab8b157a4f
            if ($options["errno"] & E_USER_NOTICE) {
                $report_html =
                     "\033[30;42m"
                    ."[REPORT] ".$errfile.'(L'.$errline.') - '.$errpos
                    ."\033[0m"."\n"
                    ."\033[32;40m"
                    .$report_html."\n"
                    ."\033[0m";
            } elseif ($options["errno"] & (E_USER_ERROR | E_ERROR)) {
                $report_html =
                     "\033[97;41m"
                    ."[ERROR] ".$errfile.'(L'.$errline.') - '.$errpos
                    ."\033[0m"."\n"
                    ."\033[31;40m"
                    .$report_html."\n"
                    ."\033[0m";
            } else {
                $report_html =
                     "\033[30;43m"
                    ."[WARNING] ".$errfile.'(L'.$errline.') - '.$errpos
                    ."\033[0m"."\n"
                    ."\033[33;40m"
                    .$report_html
                    ."\n\033[0m";
            }
            $report_html .="\n";
        }
        return $report_html;
    }
    /**
     * 値のHTML出力整形
     */
    private function decorate_value ($target_value, $html_mode=false, $level=1)
    {
        $result ="";
        $br_code =$html_mode ? "<br/>" : "\n";
        $sp_code =$html_mode ? "&nbsp;" : " ";
        if ($level > 20) {
            $result ="Report depth ".$level." too deep.";
        // } elseif ($info =self::report_profile_function($target_value)) {
        //     $result .='function: '.$info["name"].'@'.$info["file_short"].'[L'.$info["line"].']'.$br_code;
        } elseif (is_arraylike($target_value) && ! (is_object($target_value) && method_exists($target_value,"__report"))) {
            // $result .=is_object($target_value) ? get_class($target_value)." :array" : 'array';
            // $result .='('.count($target_value).'):'.$br_code;
            $result .=$br_code;
            if (count($target_value)) {
                foreach ($target_value as $key => $value) {
                    $result .=str_repeat($sp_code,$level*3).''.$key.' : '
                            .$sp_code.self::decorate_value($value,$html_mode,$level+1)
                            .$br_code;
                }
            }
        // } elseif (is_object($target_value)) {
        //     $result .='object '.get_class($target_value).
        //             '('.count($target_value).'):'.$br_code;
        //     $object_vars =method_exists($target_value,"__report")
        //             ? $target_value->__report()
        //             : get_object_vars($target_value);
        //     if ($object_vars) {
        //         foreach ($object_vars as $key => $value) {
        //             $result .=str_repeat($sp_code,$level*3).'['.$key.']=>'
        //                     .$sp_code.self::decorate_value($value,$html_mode,$level+1)
        //                     .$br_code;
        //         }
        //     }
        } else {
            // if ($target_value === null) {
            //     $result .="null";
            // } else
            if (is_string($target_value)) {
                $count =strlen($target_value);
                $target_value =$html_mode
                        ? htmlspecialchars($target_value)
                        : str_replace("\n",'\n',$target_value);
                $result .=/*"string(".$count."): ".*/(string)$target_value;
            // } elseif (is_bool($target_value)) {
            //     $result .="boolean: ".($target_value ? "true" : "false");
            } else {
                $result .=gettype($target_value).": ".(string)$target_value;
            }
        }
        return preg_replace('!'.$br_code.'$!i','',$result);
    }
    /**
     * 関数/メソッドの情報を解析する
     */
    // private function report_profile_function ($func) {
    //     $info =array();
    //     $ref =null;
    //     if (is_string($func) || is_object($func) || ! is_callable($func)) {
    //         return array();
    //     }
    //     if (is_array($func)) {
    //         if ( ! method_exists($func[0], $func[1])) {
    //             return array();
    //         }
    //         $ref =new \ReflectionMethod($func[0], $func[1]);
    //         $class_name =$ref->getDeclaringClass()->getName();
    //     } else {
    //         $ref =new \ReflectionFunction($func);
    //     }
    //     $info["name"] =$ref->getName();
    //     $info["file"] =$ref->getFileName();
    //     $info["line"] =$ref->getStartLine();
    //     $info["ns"] =$ref->getNamespaceName();
    //     $info["comment"] =$ref->getDocComment();
    //     $info["file_short"] =$info["file"];
    //     $info["params"] =array();
    //     return $info;
    // }

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
        } else {
            foreach ($values["bts"] as $bt) {
                if (strpos($bt, '(rapp)/')===0) {
                    $params["__"]["pos"] = $bt;
                }
            }
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

// -- Recordの加工

    /**
     * LoggerのRecord配列をRenderRecordに最適化
     */
    public static function compactRecord($record)
    {
        if ( ! $record["context"]["__"]["bts"]) {
            $record["context"]["__"]["bts"] = ReportRenderer::compactBacktraces(debug_backtrace());
        }
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
    public static function compactValue($value)
    {
        $r = null;
        if (is_array($value)) {
            foreach ($value as $k=>$v) {
                $r[$k] = self::compactValue($v);
            }
        } elseif (is_object($value) && method_exists($value,"__report")) {
            $r["type"] = get_class($value);
            foreach ((array)$value->__report() as $k=>$v) {
                $r[$k] = self::compactValue($v);
            }
        } elseif ( ! is_string($value) && is_callable($value)) {
            $ref = is_array($value) ? new \ReflectionMethod($value[0], $value[1]) : new \ReflectionFunction($value);
            $r = 'function '.$ref->getName().'@'.$ref->getFileName().'(L'.$ref->getStartLine().')';
        } elseif (is_object($value)) {
            $r = get_class($value);
        } elseif (is_null($value)) {
            $r = "null";
        } elseif (is_bool($value)) {
            $r = $value ? "true" : "false";
        } elseif ( ! is_string($value)) {
            $r = gettype($value)." ".$value;
        } else {
            $r = '"'.(string)$value.'"';
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
        foreach (array_reverse($bts) as $bt) {
            if ($bt['class']=='Monolog\Logger') break;
            $rs[] = self::compactBacktraceRow($bt);
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
        $app_path = realpath($vendor_path."/../../..");
        $r = "";
        $bt['file'] = realpath($bt['file']);
        if (strpos($bt['file'],$lib_path)===0) {
            $r .= "(rlib)".substr($bt['file'],strlen($lib_path));
        } elseif (strpos($bt['file'],$logger_path)===0) {
            $r .= "(logger)".substr($bt['file'],strlen($logger_path));
        } elseif (strpos($bt['file'],$vendor_path)===0) {
            $r .= "(vendor)".substr($bt['file'],strlen($vendor_path));
        } elseif (strpos($bt['file'],$app_path)===0) {
            $r .= "(rapp)".substr($bt['file'],strlen($app_path));
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
                if (is_object($arg)) {
                    $r .= get_class($arg);
                } elseif (is_array($arg)) {
                    $r .= "array[".count($arg)."]";
                } else {
                    $str = $arg;
                    $r .= '"'.(strlen($str)>10 ? substr($str,10).'...' : $str).'"';
                }
                if ($i>0) {
                    $r .=" , ";
                }
            }
            $r .= ")";
        }
        return $r;
    }

// -- Recordの描画

    /**
     * HTML描画
     */
    public static function render($record, $format)
    {
        $message = $record['message'];
        $params = $record["context"];
        $level = $record["level"];
        $pos = $params["__"]["pos"];
        $bts = $params["__"]["bts"];
        unset($params["__"]);
        // HTML形式
        if ($format=="html") {
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
                .'#000000;cursor:hand;height:40px;color:'.$c.'">'.$pos
                .'<div style="margin:0 0 0 10px">'.$message.self::indentValues($params, $format).'</div>'
                .'<div style="margin:0 0 0 10px;display:none;" id="'.$elm_id.'_detail">'
                .'Backtraces<br/>'.self::indentValues($bts, $format)
                .'</div></div>';
        // Console形式
        } elseif ($format=="console") {
            $text = "";
            $text .= "*\n* ".$message."\n*";
            $text .= "\n[PARAM] ".self::indentValues($params, $format);
            $text .= "\n[TRACE] ".self::indentValues($bts, $format);
            // http://qiita.com/hidai@github/items/1704bf2926ab8b157a4f
            $c = array("n"=>"\033[0m", "bg"=>"\033[30;42m", "fg"=>"\033[32;40m");
            if ($level >= Logger::ERROR) $c = array("n"=>"\033[0m", "bg"=>"\033[97;41m", "fg"=>"\033[31;40m");
            elseif ($level >= Logger::WARNING) $c = array("n"=>"\033[0m", "bg"=>"\033[30;43m", "fg"=>"\033[33;40m");
            return $c["bg"]."[".$level."] ".$pos.$c["n"]."\n"
                .$c["fg"].$text.$c["n"]."\n";
        }
    }
    /**
     * 値のHTML出力整形
     */
    private static function indentValues ($values, $format, $level=1)
    {
        $tab_code = $format==="html" ? "&nbsp;&nbsp;&nbsp;&nbsp;" : "    ";
        $br_code = $format==="html" ? "<br/>" : "\n";
        if ($level > 10) {
            return "*** DEPTH over 10 ***";
        }
        $text = "";
        foreach ($values as $k=>$v) {
            $text .= $br_code.str_repeat($tab_code, $level).$k." : ";
            if (is_array($v) && count($v)) {
                $text .= self::indentValues($v,$format,$level+1);
            } elseif (is_array($v) && ! count($v)) {
                $text .= "[]";
            } else {
                $text .= $html_mode ? htmlspecialchars($v) : str_replace("\n",'\n',$v);
            }
        }
        return $text;
    }

// -- @deprecated

    public static function compactContext($context)
    {
        foreach ($context as $k=>$v) {
            if ($k==="__") {
                $context[$k]["backtraces"] = self::compactBacktrace($context[$k]["backtraces"]);
            } else {
                $context[$k] = self::compactValue($v);
            }
        }
        return $context;
    }
    public static function compactBacktrace($backtrace)
    {
        foreach ((array)$backtrace as $k1=>$v1) {
            unset($backtrace[$k1]["args"]);
            unset($backtrace[$k1]["object"]);
        }
        return $backtrace;
    }
}
