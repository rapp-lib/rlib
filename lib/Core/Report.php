<?php
namespace R\Lib\Core;

/**
 *
 */
class Report
{
    private static $instance;
    /**
     * シングルトンインスタンスの取得
     */
    public static function getInstance ()
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new Report;
        }
        return self::$instance;
    }
    /**
     * 標準エラーと警告を取得するように設定
     */
    public function install ()
    {
        set_error_handler(function($errno, $errstr, $errfile=null, $errline=null, $errcontext=null) {
            report()->error("[Error] ".$errstr, array("context"=>$errcontext), array(
                "file" => $errfile,
                "line" => $errline,
            ));
        },E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_PARSE | E_USER_ERROR);
        set_error_handler(function($errno, $errstr, $errfile=null, $errline=null, $errcontext=null) {
            report()->warning("[Warning] ".$errstr, array("context"=>$errcontext), array(
                "file" => $errfile,
                "line" => $errline,
            ));
        },E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING);
        set_exception_handler(function($e) {
            report()->error("[Uncaught ".get_class($e)."] ".$e->getMessage(), array("exception"=>$e), array(
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "backtraces" => $e->getTrace(),
            ));
        });
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && $error['type'] ^ (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_PARSE | E_USER_ERROR)) {
                app()->ending();
                report()->error("[Fatal] ".$error["message"] ,array("error"=>$error) ,array(
                    "file" =>$error['file'],
                    "line" =>$error['line'],
                ));
            }
        });
    }
    /**
     *
     */
    public function log ($message, $params=array(), $options=array())
    {
        // レポート出力判定
        if (app()->getDebugLevel()) {
            $backtraces = $options["backtraces"] ? $options["backtraces"] : debug_backtrace();
            $str_log = $this->formatLog($message, $params, $options, $backtraces);
            app()->log($str_log);
        }

        // エラー時の処理停止
        if ($options["errno"] & (E_USER_ERROR | E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)) {

            response()->error($errstr,array(
                "params" =>$params,
                "options" =>$options,
            ));
        }
    }
    /**
     *
     */
    public function warning ($message, $params=array(), $options=array())
    {
        $options["level"] = "warning";
        $this->log(array("warning", $message, $params, $options));
    }
    /**
     *
     */
    public function error ($message, $params=array(), $options=array())
    {
        $options["level"] = "error";
        $this->log(array("error", $message, $params, $options));
    }

// -- 整形処理

    /**
     * レポートを文字列として整形
     * @param  [type] $errstr     [description]
     * @param  [type] $params     [description]
     * @param  array  $options    [description]
     * @param  array  $backtraces [description]
     * @param  array  $config     [description]
     * @return [type]             [description]
     */
    private function formatLog ($errstr, $params=null, $options=array(), $backtraces=null)
    {
        $libpath =realpath(dirname(__FILE__)."/../..");
        $report_filepath =realpath(__FILE__);

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

            if ($backtrace['file'] == $report_filepath) {

                continue;
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

            if (strlen($backtrace['line']) && strstr($backtrace['file'],$libpath)===false) {

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

                $message .=decorate_value($errstr,true);
            }

            if ($params!==null) {

                $message .=' :'.decorate_value($params,true);
            }

            if (registry("Report.report_backtraces")) {

                $message .='<br/> [BACKTRACES] :'.decorate_value($backtraces,true);
            }

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
                    .'Backtrace: '.decorate_value(array_reverse($errdetail),true).'</div></div>';

        // 非HTML形式
        } elseif ( ! $html_mode) {

            if ($options["errno"] & E_USER_NOTICE) {

                $report_html .="[REPORT] ";

            } elseif ($options["errno"] & (E_USER_ERROR | E_ERROR)) {

                $report_html .="[ERROR] ";

            } else {

                $report_html .="[WARNING] ";
            }

            $report_html .=$errfile.'(L'.$errline.') - '.$errpos."\n";

            if (is_string($errstr)) {

                $report_html .=$errstr;

            } else {

                $report_html .=decorate_value($errstr,false);
            }

            if (is_array($params)) {

                $report_html .=' : '.decorate_value($params,false);
            }

            if (is_array($errdetail)) {

                $report_html .=' : Backtrace='.decorate_value(array_reverse($errdetail),false);
            }

            $report_html .="\n\n";
        }

        return $report_html;
    }
    /**
     * 値の整形
     * @param  [type]  $target_value [description]
     * @param  boolean $html_mode    [description]
     * @param  integer $level        [description]
     * @return [type]                [description]
     */
    private function formatValue ($target_value, $html_mode=false, $level=1)
    {
        $result ="";
        $br_code =$html_mode ? "<br/>" : "\n";
        $sp_code =$html_mode ? "&nbsp;" : " ";

        if ($level > 20) {

            $result ="Report depth ".$level." too deep.";

        } elseif ($info =report_profile_function($target_value)) {

            $result .='function: '.$info["name"].'@'.$info["file_short"].'[L'.$info["line"].']'.$br_code;

        } elseif (is_arraylike($target_value)) {

            $result .=is_object($target_value) ? get_class($target_value)." :array" : 'array';
            $result .='('.count($target_value).'):'.$br_code;

            if (count($target_value)) {

                foreach ($target_value as $key => $value) {

                    $result .=str_repeat($sp_code,$level*3).'['.$key.']=>'
                            .$sp_code.decorate_value($value,$html_mode,$level+1)
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
                            .$sp_code.decorate_value($value,$html_mode,$level+1)
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
     * 関数/メソッドの情報を文字列として整形
     * @param  [type] $func [description]
     * @return [type]       [description]
     */
    private function formatFunction ($func)
    {
        $info =array();
        $ref =null;

        if (is_string($func) || ! is_callable($func)) {

            return array();
        }

        if (is_array($func)) {
            if ( ! method_exists($func[0], $func[1])) {
                return array();
            }
            $ref =new ReflectionMethod($func[0], $func[1]);
            $class_name =$ref->getDeclaringClass()->getName();

        } else {

            $ref =new ReflectionFunction($func);
        }

        $info["name"] =$ref->getName();
        $info["file"] =$ref->getFileName();
        $info["line"] =$ref->getStartLine();
        $info["ns"] =$ref->getNamespaceName();
        $info["comment"] =$ref->getDocComment();
        $info["file_short"] =self::to_short_filename($info["file"]);

        $info["params"] =array();

        return $info;
    }
}