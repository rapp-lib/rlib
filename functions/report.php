<?php


    //-------------------------------------
    // 標準レポートハンドラ
    function std_error_handler (
            $errno,
            $errstr,
            $errfile=null,
            $errline=null,
            $errcontext=null) {

        if ( ! (get_webapp_dync("report")
                && (registry("Report.error_reporting") & $errno)
                && (error_reporting()!==0))) {

            return;
        }

        report($errstr,$errcontext,array(
            "type" =>"error_handler",
            "errno" =>$errno,
            "errstr" =>$errstr,
            "errfile" =>$errfile,
            "errline" =>$errline,
        ));
    }

    //-------------------------------------
    // 標準例外ハンドラ
    function std_exception_handler ($e) {

        try {

            if (is_a($e,"ReportError")) {

                throw $e;
            }

            report("[".get_class($e)."] ".$e->getMessage(),array(
                "exception" =>$e,
            ),array(
                "type" =>"exception_handler",
                "errno" =>E_ERROR,
                "errstr" =>$e->getMessage(),
                "errfile" =>$e->getFile(),
                "errline" =>$e->getLine(),
                "code" =>$e->getCode(),
                "exception" =>$e,
            ));

        } catch (ReportError $e_report) {

            $e_report->shutdown();
        }
    }

    //-------------------------------------
    // 値のHTML出力整形
    function decorate_value ($target_value, $html_mode=false, $level=1) {

        $result ="";
        $br_code =$html_mode ? "<br/>" : "\n";
        $sp_code =$html_mode ? "&nbsp;" : " ";

        if ($level > 20) {

            $result ="Report depth ".$level." too deep.";

        } elseif ($info =VarsProfiler::profile_function($target_value)) {

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

    function report_template (
            $errstr,
            $params=null,
            $options=array(),
            $backtraces=array(),
            $config=array()) {

        $libpath =realpath(dirname(__FILE__)."/..");
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

    //-------------------------------------
    // レポートドライバ
    function report (
            $errstr,
            $params=null,
            $options=array()) {

        $options["errno"] =$options["errno"]
                ? $options["errno"]
                : E_USER_NOTICE;

        $backtraces =$options["backtraces"]
                ? $options["backtraces"]
                : debug_backtrace();

        // レポート出力判定
        if (get_webapp_dync("report")
                && (registry("Report.error_reporting") & $options["errno"])) {

            $config =array();
            $config["output_format"] = ! get_cli_mode() && ! registry("Report.output_to_file") ? "html" : "plain";
            $html =report_template($errstr,$params,$options,$backtraces,$config);

            // ファイル出力
            if ($file_name =registry("Report.output_to_file")) {

                file_put_contents($file_name,$html,FILE_APPEND|LOCK_EX);
                chmod($file_name,0777);

            // Report.buffer_enableによる出力抑止
            } else if ($buffer_level =registry("Report.buffer_enable")) {

                $report_buffer =& ref_globals("report_buffer");
                $report_buffer[$buffer_level] .=$html;

            // 直接出力
            } else {

                print $html;
            }
        }

        // エラー時の処理停止
        if ($options["errno"] & (E_USER_ERROR | E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)) {

            throw new ReportError(array(
                "errstr" =>$errstr,
                "options" =>$options,
                "params" =>$params,
                "backtraces" =>$backtraces,
            ));
        }
    }

    //-------------------------------------
    //
    function report_warning ($message, $params=array(), $options=array()) {

        $options["errno"] =E_USER_WARNING;
        report($message,$params,$options);
    }

    //-------------------------------------
    //
    function report_error ($message, $params=array(), $options=array()) {

        $options["errno"] =E_USER_ERROR;
        report($message,$params,$options);
    }

    //-------------------------------------
    //
    function report_buffer_start () {

        $buffer_level =registry("Report.buffer_enable");
        registry("Report.buffer_enable",$buffer_level+1);
    }

    //-------------------------------------
    //
    function report_buffer_end ($all=false) {

        $buffer_level =registry("Report.buffer_enable");

        // 開始していなければ処理を行わない
        if ( ! $buffer_level) {

            return;
        }

        $report_buffer =& ref_globals("report_buffer");
        $output =$report_buffer[$buffer_level];
        unset($report_buffer[$buffer_level]);

        registry("Report.buffer_enable",--$buffer_level);

        if ($buffer_level > 0) {

            $report_buffer[$buffer_level] .=$output;

        } else {

            print $output;
        }

        // 全件終了
        if ($all) {

            report_buffer_end($all);
        }
    }

//-------------------------------------
//
class ReportError extends ErrorException {

    protected $report_vars;

    //-------------------------------------
    //
    public function __construct ($report_vars=array()) {

        parent::__construct(
            $report_vars["options"]["errstr"],
            $report_vars["options"]["code"] ? $params["options"]["code"] : 0,
            $report_vars["options"]["errno"],
            $report_vars["options"]["errfile"],
            $report_vars["options"]["errline"]
        );
        $this->report_vars =$report_vars;
    }

    //-------------------------------------
    //
    public function shutdown () {

        shutdown_webapp("error_report",$this->report_vars);
    }
}
