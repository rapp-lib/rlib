<?php
    function install_debug ()
    {
        if (app()->hasProvider("debug") && app()->debug()) {
            if ($_POST["__rdoc"]["entry"]==="build_rapp") {
                if (app()->hasProvider("builder")) {
                    app()->builder->start();
                }
            }
        }
    }
    function install_report ()
    {
        // 終了処理
        register_shutdown_function(function() {
            // エラーによる終了のLogging
            $error = error_get_last();
            $error_type = $error['type'];
            if ($error && $error_type & (E_ERROR|E_PARSE|E_CORE_ERROR|E_COMPILE_ERROR)) {
                $report = array();
                $report["message"] = $error["message"];
                $report["vars"] = array();
                $report["errno"] = E_ERROR;
                $report["errfile"] = $error['file'];
                $report["errline"] = $error['line'];
                $report["unrecoverable"] = true;
                report($report["message"],$report["vars"],$report);
            }
        });
        set_exception_handler(function($e) {
            try {
                if ($e instanceof \R\Lib\Core\Exception\ResponseException) {
                    $response = $e->getResponse();
                    $response->render();
                    return;
                } else {
                    $report = array();
                    $report["message"] = "Uncaught ".get_class($e).". ".$e->getMessage();
                    $report["vars"] = array("exception"=>$e);
                    $report["errno"] = E_ERROR;
                    $report["errfile"] = $e->getFile();
                    $report["errline"] = $e->getLine();
                    $options["backtraces"] = $e->getTrace();
                    $report["unrecoverable"] = true;
                    report($report["message"],$report["vars"],$report);
                    return;
                }
            } catch (\Exception $e_internal) {
                //set_error_handler(null);
                //set_exception_handler(null);
                throw $e_internal;
            }
        });
        set_error_handler(function($errno, $errstr, $errfile=null, $errline=null, $errcontext=null) {
            if ($errno&(E_ERROR|E_USER_ERROR)) {
                //set_error_handler(null);
            }
            if ($errno&(E_ERROR|E_WARNING)) {
                $report = array();
                $report["type"] = $errno&E_ERROR ? "error" : "warning";
                $report["message"] = $errstr;
                $report["vars"] = array("context"=>$errcontext);
                $report["errno"] = $errno;
                $report["errfile"] = $errfile;
                $report["errline"] = $errline;
                report($report["message"],$report["vars"],$report);
            } elseif ($errno&(E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE)) {
                $report = $GLOBALS["__REPORT_LAST"];
                unset($GLOBALS["__REPORT_LAST"]);
                report($report["message"],$report["vars"],$report);
            }
        },E_ERROR|E_WARNING|E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE);
    }
    function report_info ($message, $vars=array(), $report=array())
    {
        $report["type"] = "info";
        $report["errno"] = E_USER_NOTICE;
        $report["message"] = $message;
        $report["vars"] = $vars;
        $GLOBALS["__REPORT_LAST"] = $report;
        trigger_error($message, E_USER_NOTICE);
    }
    function report_warning ($message, $vars=array(), $report=array())
    {
        $report["type"] = "warning";
        $report["errno"] = E_USER_WARNING;
        $report["message"] = $message;
        $report["vars"] = $vars;
        $GLOBALS["__REPORT_LAST"] = $report;
        trigger_error($message, E_USER_WARNING);
    }
    function report_error ($message, $vars=array(), $report=array())
    {
        $report["type"] = "error";
        $report["errno"] = E_USER_ERROR;
        $report["message"] = $message;
        $report["vars"] = $vars;
        $GLOBALS["__REPORT_LAST"] = $report;
        trigger_error($message, E_USER_ERROR);
    }

    //-------------------------------------
    // 値のHTML出力整形
    function decorate_value ($target_value, $html_mode=false, $level=1) {

        $result ="";
        $br_code =$html_mode ? "<br/>" : "\n";
        $sp_code =$html_mode ? "&nbsp;" : " ";

        if ($level > 20) {

            $result ="Report depth ".$level." too deep.";

        } elseif ($info =report_profile_function($target_value)) {

            $result .='function: '.$info["name"].'@'.$info["file_short"].'[L'.$info["line"].']'.$br_code;

        } elseif (is_arraylike($target_value) && ! (is_object($target_value) && method_exists($target_value,"__report"))) {

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

        if ($e = $options["exception"]) {
            $options["errstr"] =$e->getMessage();
            $options["errfile"] =$e->getFile();
            $options["errline"] =$e->getLine();
            $options["code"] =$e->getCode();
            $options["backtraces"] =$e->getTrace();
        }

        $backtraces =$options["backtraces"]
                ? $options["backtraces"]
                : debug_backtrace();

        // レポート出力判定
        if (app() && app()->hasProvider("debug") && app()->debug()) {

            $config =array();
            $config["output_format"] = ! (php_sapi_name()=="cli")/* && ! registry("Report.output_to_file")*/ ? "html" : "plain";
            $html =report_template($errstr,$params,$options,$backtraces,$config);

            // ファイル出力
            if (false/*$file_name =registry("Report.output_to_file")*/) {

                file_put_contents($file_name,$html,FILE_APPEND|LOCK_EX);
                chmod($file_name,0777);

            // Report.buffer_enableによる出力抑止
            } elseif ($GLOBALS["__REPORT_BUFFER_LEVEL"]) {

                $GLOBALS["__REPORT_BUFFER"] .= $html;

            // 直接出力
            } else {

                print $html;
            }
        }

        // エラー時の処理停止
        if ($options["errno"] & (E_USER_ERROR|E_ERROR)) {
            if (app() && app()->hasProvider("response")) {
                try {
                    $response = app()->response->error($options["response_message"], $options["response_code"]);
                } catch (R\Lib\Core\Exception\ResponseException $e) {
                    $response = $e->getResponse();
                }
                if ($options["unrecoverable"]) {
                    $response->render();
                } else {
                    $response->raise();
                }
            } else {
                throw new \Exception($errstr);
            }
        }
    }

    function report_buffer_start ()
    {
        $GLOBALS["__REPORT_BUFFER_LEVEL"] += 1;
    }
    function report_buffer_end ($all=false)
    {
        // 全件終了
        if ($all) {
            $GLOBALS["__REPORT_BUFFER_LEVEL"] = 1;
        }
        // 開始していなければ処理を行わない
        if ($GLOBALS["__REPORT_BUFFER_LEVEL"] > 0) {
            $GLOBALS["__REPORT_BUFFER_LEVEL"] -= 1;
            if ($GLOBALS["__REPORT_BUFFER_LEVEL"] == 0) {
                print $GLOBALS["__REPORT_BUFFER"];
                $GLOBALS["__REPORT_BUFFER"] = "";
            }
        }
    }

    /**
     * 関数/メソッドの情報を解析する
     */
    function report_profile_function ($func) {

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
        $info["file_short"] =$info["file"];

        $info["params"] =array();

        return $info;
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
