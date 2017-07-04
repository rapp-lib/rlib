<?php
namespace R\Lib\Report;
use Monolog\Logger;

class ReportRenderer
{
    public function render ($record)
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
            $params["__"]["backtraces"] = "*";
        }
        $backtraces = $options["backtraces"] ?: debug_backtrace();

        $config =array();
        $config["output_format"] = ! (php_sapi_name()=="cli") ? "html" : "plain";
        $html = self::report_template($errstr,$params,$options,$backtraces,$config);
        return $html;
    }
    private function report_template ($errstr, $params, $options, $backtraces, $config)
    {
        $libpath = realpath(dirname(__FILE__)."/../..");
        $vendor_path = realpath($libpath."/../..");
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
            if (in_array($backtrace['file'],$report_filepaths)) {
                $errdetail = array();
            }
            $errdetail[$i] ='';
            $errdetail[$i] .=strstr($backtrace['file'],$libpath)!==false ? "rlib/" : "";
            $errdetail[$i] .=basename($backtrace['file']);
            $errdetail[$i] .=($backtrace['line'] ? "(L".$backtrace['line'].") " : "");
            $errdetail[$i] .=' - ';
            $errdetail[$i] .=$backtrace['class'] ? $backtrace['class'].$backtrace['type'] : "";
            $errdetail[$i] .=$backtrace['function'] ? $backtrace['function'] : "";
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
                $message .=' :'.self::decorate_value($params,true);
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
                    .'margin:1px;padding:2px;font-family:monospace;'
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
        } elseif ($info =self::report_profile_function($target_value)) {
            $result .='function: '.$info["name"].'@'.$info["file_short"].'[L'.$info["line"].']'.$br_code;
        } elseif (is_arraylike($target_value) && ! (is_object($target_value) && method_exists($target_value,"__report"))) {
            $result .=is_object($target_value) ? get_class($target_value)." :array" : 'array';
            $result .='('.count($target_value).'):'.$br_code;
            if (count($target_value)) {
                foreach ($target_value as $key => $value) {
                    $result .=str_repeat($sp_code,$level*3).'['.$key.']=>'
                            .$sp_code.self::decorate_value($value,$html_mode,$level+1)
                            .$br_code;
                }
            }
        } elseif (is_object($target_value)) {
            $result .='object '.get_class($target_value).
                    '('.count($target_value).'):'.$br_code;
            $object_vars =method_exists($target_value,"__report")
                    ? $target_value->__report()
                    : get_object_vars($target_value);
            if ($object_vars) {
                foreach ($object_vars as $key => $value) {
                    $result .=str_repeat($sp_code,$level*3).'['.$key.']=>'
                            .$sp_code.self::decorate_value($value,$html_mode,$level+1)
                            .$br_code;
                }
            }
        } else {
            if ($target_value === null) {
                $result .="null";
            } elseif (is_string($target_value)) {
                $count =strlen($target_value);
                $target_value =$html_mode
                        ? htmlspecialchars($target_value)
                        : str_replace("\n",'\n',$target_value);
                $result .="string(".$count."): ".(string)$target_value;
            } elseif (is_bool($target_value)) {
                $result .="boolean: ".($target_value ? "true" : "false");
            } else {
                $result .=gettype($target_value).": ".(string)$target_value;
            }
        }
        return preg_replace('!'.$br_code.'$!i','',$result);
    }
    /**
     * 関数/メソッドの情報を解析する
     */
    private function report_profile_function ($func) {
        $info =array();
        $ref =null;
        if (is_string($func) || is_object($func) || ! is_callable($func)) {
            return array();
        }
        if (is_array($func)) {
            if ( ! method_exists($func[0], $func[1])) {
                return array();
            }
            $ref =new \ReflectionMethod($func[0], $func[1]);
            $class_name =$ref->getDeclaringClass()->getName();
        } else {
            $ref =new \ReflectionFunction($func);
        }
        $info["name"] =$ref->getName();
        $info["file"] =$ref->getFileName();
        $info["line"] =$ref->getStartLine();
        $info["ns"] =$ref->getNamespaceName();
        $info["comment"] =$ref->getDocComment();
        $info["file_short"] =$info["file"];
        $info["params"] =array();
        return $info;
    }
}
