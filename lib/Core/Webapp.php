<?php
/*
    2016/07/07
        置き換え対象が多いためメモ
        registry -> Vars::registry
        ref_globals -> Vars::refGlobals
        ref_session -> Vars::refSession
        sanitize -> String::sanitizeRequest
        encrypt_string -> String::encrypt
        add_include_path -> Modules::addIncludePath
        load_lib -> Modules::loadLib
        report -> Report::report
        report_buffer_end -> Report::reportBufferEnd
        report_error -> Report::reportError
        tag -> Html::tag
 */
namespace R\Lib\Core;

use R\Lib\Core\Vars;
use R\Lib\Core\String;
use R\Lib\Core\Path;
use R\Lib\Core\Report;
use R\Lib\Core\Html;

/**
 *
 */
class Webapp {

    /**
     * [start_webapp description]
     */
    public static function startWebapp () {

        // Registryのデフォルト値の補完
        $registry_defaultset =array(

            // パス設定
            "Path.lib_dir" =>RLIB_ROOT_DIR,
            "Path.tmp_dir" =>"/tmp",
            "Path.document_root_dir" =>realpath($_SERVER["DOCUMENT_ROOT"]),
            "Path.webapp_dir" =>realpath(dirname($_SERVER['SCRIPT_FILENAME'])."/.."),
            "Path.html_dir" =>realpath(dirname($_SERVER['SCRIPT_FILENAME'])),

            // エンコーディング設定
            "Config.internal_charset" =>"UTF-8",
            "Config.external_charset" =>"SJIS-WIN",

            // Dync機能設定
            "Config.dync_key" =>null,

            // セッション設定
            "Config.session_lifetime" =>86400,
            "Config.session_start_function" =>"std_session_start",

            // webapp_dir内のinclude_path設定
            "Config.webapp_include_path" =>array(
                "app",
                "app/include",
                "app/controller",
                "app/context",
                "app/list",
                "app/model",
                "app/widget",
            ),

            // ライブラリ読み込み設定
            "Config.load_lib" =>array(
                "lib_context",
                "lib_db",
                "lib_smarty",
            ),

            // レポート出力設定
            "Report.error_reporting" =>E_ALL&~E_NOTICE&~E_STRICT&~E_DEPRECATED,
        );

        foreach ($registry_defaultset as $k => $v) {

            if (Vars::registry($k) === null) {

                Vars::registry($k,$v);
            }
        }

        // php.ini設定
        foreach ((array)Vars::registry("Config.php_ini") as $k => $v) {

            ini_set($k, $v);
        }

        // HTTPパラメータ構築
        $_REQUEST =array_merge($_GET,$_POST);

        // 入出力文字コード変換
        ob_start("mb_output_handler_impl");
        mb_convert_variables(
                Vars::registry("Config.internal_charset"),
                Vars::registry("Config.external_charset"),
                $_REQUEST);
        $_REQUEST =String::sanitizeRequest($_REQUEST);

        // PHPの設定書き換え
        spl_autoload_register("load_class");
        set_error_handler("std_error_handler",E_ALL);
        set_exception_handler('std_exception_handler');
        register_shutdown_function('std_shutdown_handler');

        if ( ! Webapp::getCliMode()) {

            // session_start
            call_user_func(Vars::registry("Config.session_start_function"));

            // Dync機能の有効化
            Webapp::startDync();
        }

        // include_pathの設定
        foreach ((array)Vars::registry("Config.webapp_include_path") as $k => $v) {

            Modules::addIncludePath(Vars::registry("Path.webapp_dir")."/".$v);
        }

        // ライブラリの読み込み
        foreach ((array)Vars::registry("Config.load_lib") as $k => $v) {

            Modules::loadLib($v);
        }

        obj("Rdoc")->check();
    }

    /**
     * [std_session_start description]
     */
    public static function stdSessionStart () {

        // セッションの開始
        $session_lifetime =Vars::registry("Config.session_lifetime");
        ini_set("session.gc_maxlifetime",$session_lifetime);
        ini_set("session.cookie_lifetime",$session_lifetime);
        ini_set("session.cookie_httponly",true);
        ini_set("session.cookie_secure",$_SERVER['HTTPS']);

        // Probrem on IE and https filedownload
        // http://www.php.net/manual/en/function.session-cache-limiter.php#48822
        session_cache_limiter('');
        header("Pragma: public");
        header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");

        header("P3P: CP='UNI CUR OUR'");

        session_start();
    }

    /**
     * [start_dync description]
     */
    public static function startDync () {

        if ($dync_key =Vars::registry("Config.dync_key")) {

            $dync =(array)$_SESSION["__dync"];

            $sec =$_REQUEST["__ts"];
            $min =floor(time()/60);
            $sec_list =array();

            foreach (range(-5,5) as $i) {

                $sec_list[$i] =String::encrypt(substr(md5($dync_key."/".($min+$i)),12,12));
            }

            if ($_REQUEST[$dync_key] && $sec && (in_array($sec, $sec_list))) {

                $dync["auth"] =$dync_key;
            }

            if ($dync["auth"]) {

                $dync =array_merge($dync,(array)$_REQUEST[$dync_key]);

                $_SESSION["__dync"] =$dync;

                Vars::registry("Config.dync",$dync);

                if ($dync["report"]) {

                    //ini_set("display_errors",true);
                    //ini_set("error_reporting",Vars::registry("Report.error_reporting"));
                }
            }

            if (Vars::registry("Report.report_about_dync")) {

                Report::report("Dync status-report.",array(
                    "request_ts" =>$_REQUEST["__ts"],
                    "server_dync_key" =>$dync_key,
                    "server_min" =>date("Y/m/d H:i",time()),
                    "server_ts_threashold" =>$sec_list,
                    "request_dync" =>$_REQUEST[$dync_key],
                    "session_dync" =>$_SESSION["__dync"],
                ));
            }
        }
    }

    /**
     * [mb_output_handler_impl description]
     * @param  [type] $html [description]
     * @return [type]      [description]
     */
    public static function mbOutputHandlerImpl ($html) {

        $html =mb_convert_encoding(
                $html,
                Vars::registry("Config.external_charset"),
                Vars::registry("Config.internal_charset"));

        return $html;
    }

    /**
     * [clean_output_shutdown description]
     * @param  [type] $output [description]
     * @return [type]      [description]
     */
    public static function cleanOutputShutdown ($output) {

        // download
        if (is_array($output) && $output["download"]) {

            $download_filename =$output["download"];

            if ( ! $download_filename) {

                if (is_array($output) && $output["file"]) {

                    $download_filename =basename($output["file"]);

                } else {

                    $download_filename ="noname";
                }
            }

            header("Content-Disposition: attachment; filename=".$download_filename);
        }

        // content_type
        if (is_array($output) && $output["content_type"]) {

            header("Content-Type: ".$output["content_type"]);

        } else if (is_array($output) && $output["download"]) {

            header("Content-Type: application/octet-stream");

        } else if (is_array($output) && $output["json"]) {

            header("Content-Type: application/json");

        } else if (is_array($output) && $output["file"]) {

            $info =getimagesize($output["file"]);

            if ($info['mime']) {

                header("Content-Type: ".$info['mime']);
            }
        }

        // ここまでの出力をバッファに転送
        $clean_output_buffer =& Vars::refGlobals("clean_output_buffer");

        while (ob_get_level()) {

            $clean_output_buffer .=ob_get_clean();
        }

        // 出力部分をバッファ経由で出力
        ob_start();

        // output
        if (is_string($output)) {

            echo $output;

        // data
        } else if (is_array($output) && $output["data"]) {

            echo $output["data"];

        // json
        } else if (is_array($output) && $output["json"]) {

            echo array_to_json((array)$output["json"]);

        // file
        } else if (is_array($output) && $output["file"]) {

            readfile($output["file"]);
        }

        // 以降の出力をバッファに転送
        ob_start("send_to_clean_output_buffer");

        Webapp::shutdownWebapp("clean_output");
    }

    /**
     * [send_to_clean_output_buffer description]
     * @param  [type] $output [description]
     * @return [type]      [description]
     */
    // clean_output_shutdown以降の出力をバッファに転送するob処理
    public static function sendToCleanOutputBuffer ($output) {

        $clean_output_buffer =& Vars::refGlobals("clean_output_buffer");

        $clean_output_buffer .=$output;

        return "";
    }

    /**
     * [shutdown_webapp_for_ajaxr description]
     * @param  [type] $cause [description]
     * @param  [type] $options [description]
     * @return [type]      [description]
     */
    // ajaxrレスポンスへの出力変換を行うshutdown_webapp_function
    public static function shutdownWebappForAjaxr ($cause, $options) {

        $res =array();

        $clean_output_buffer =& Vars::refGlobals("clean_output_buffer");

        if ($cause=="clean_output") {

            ob_end_clean();

        } else {

            while (ob_get_level()) {

                $clean_output_buffer .=ob_get_clean();
            }
        }

        $res["type"] =$cause;

        if ($cause=="clean_output") {

            $res["response"] =ob_get_clean();

        } else if ($cause=="redirect") {

            $res["url"] =$options["url"];

        } else if ($cause=="error_report") {

            if (Webapp::getWebappDync("report")) {

                $res["message"] =$options["errstr"];

            } else {

                $res["message"] ="ERROR";
            }
        } else if ($cause=="normal") {

            $res["response"] =& $clean_output_buffer;
        }

        if (Webapp::getWebappDync("report") && $cause!="normal") {

            $res["report"] ="on";
            $res["buffer"] =& $clean_output_buffer;
        }

        print array_to_json($res);
    }

    /**
     * [output_rewrite_var description] 非推奨のため削除予定
     * @param  string $name [description]
     * @param  string $value [description]
     * @return [type]      [description]
     */
    // [Deprecated] SEO的に無差別にURLを書き換えることは問題が大きいため非推奨 151003
    public static function outputRewriteVar ($name=null, $value=null) {

        $output_rewrite_var =& Vars::refGlobals("output_rewrite_var");
        $result =Arr::registry($output_rewrite_var,$name,$value);

        if ($value !== null) {

            output_add_rewrite_var($name,$value);

            Vars::registry('State.is_url_rewrited',true);
        }

        return $result;
    }

    /**
     * [redirect_rewrite_var description] 非推奨のため削除予定
     * @param  string $name [description]
     * @param  string $value [description]
     * @return [type]      [description]
     */
    // [Deprecated] 使用されていないため削除予定 151003
    public static function redirectRewriteVar ($name=null, $value=null) {

        $rewrite_var =& Vars::refGlobals("redirect_rewrite_var");
        $result =Arr::registry($rewrite_var,$name,$value);

        return $result;
    }

    /**
     * [add_url_rewrite_rule description]
     * @param  string $patterns [description]
     * @param  string $var_name [description]
     * @param  string $value [description]
     * @param  [type] $info [description]
     * @return [type]      [description]
     */
    // URL書き換え規則の追加
    public static function addUrlRewriteRule ($patterns, $var_name, $value, $info=array()) {

        $url_rewrite_rules =& Vars::refGlobals("url_rewrite_rules");
        $url_rewrite_rules[] =array($patterns, $var_name, $value, $info);
    }

    /**
     * [apply_url_rewrite_rules description]
     * @param  string $url [description]
     * @return [type]      [description]
     */
    // URL書き換え規則の適用
    public static function applyUrlRewriteRules ($url) {

        $url_rewrite_rules =& Vars::refGlobals("url_rewrite_rules");

        $path =Path::urlToPath($url);
        $params =array();

        foreach ((array)$url_rewrite_rules as $url_rewrite_rule) {

            list($patterns, $var_name, $value, $info) =$url_rewrite_rule;

            if ( ! is_array($patterns)) {

                $patterns =array($patterns);
            }

            if (in_path($path,$patterns)) {

                $params[$var_name] =$value;
            }
        }

        return Html::url($url, $params);
    }

    /**
     * [shutdown_webapp description]
     * @param  string $cause [description]
     * @param  [type] $options [description]
     * @return [type]      [description]
     */
    // 処理を停止するexit相当の機能/異常終了を正しく通知できる
    public static function shutdownWebapp ($cause=null, $options=array()) {

        if (defined("WEBAPP_SHUTDOWN_CAUSE")) {

            return;
        }

        define("WEBAPP_SHUTDOWN_CAUSE",$cause);

        // 通常終了時はFlushMessageを削除
        if ($cause == "normal") {

            Webapp::flushMessage(false);
        }

        // register_shutdown_webapp_functionで登録された処理の実行
        $funcs =& Vars::refGlobals('shutdown_webapp_function');

        foreach ((array)$funcs as $func) {

            call_user_func_array($func,array(
                $cause,
                $options
            ));
        }

        exit;
    }

    /**
     * [register_shutdown_webapp_function description]
     * @param  [type] $func [description]
     * @return [type]      [description]
     */
    // 全PHP処理終了時に呼び出す関数の設定
    public static function registerShutdownWebappFunction ($func) {

        $funcs =& Vars::refGlobals('shutdown_webapp_function');
        $funcs[] =$func;
    }

    /**
     * [std_shutdown_handler [description]
     */
    // 標準PHP終了ハンドラ
    public static function stdShutdownHandler () {

        Report::reportBufferEnd(true);

        $error =error_get_last();

        if ( ! defined("WEBAPP_SHUTDOWN_CAUSE")) {

            // FatalErrorによる強制終了
            if ($error) {

                try {

                    std_error_handler(
                        $error['type'],
                        "Fatal Error. ".$error['message'],
                        $error['file'],
                        $error['line']
                    );

                } catch (ReportError $e_report) {

                    $e_report->shutdown();
                }

            // shutdown_webappを経由しない不正な終了
            } else {

                Report::reportWarning("Illegal shutdown, Not routed shutdown_webapp");
            }
        }
    }

    /**
     * [elapse [description]
     * @param  [type] $event [description]
     * @param  boolean $stop [description]
     * @return [type]      [description]
     */
    // 実行実時間の計測
    public static function elapse ($event=null,$stop=false) {

        static $time =array();

        if ( ! $event) {

            return (array)$time["interval"];
        }

        if ($stop && $time["start"][$event]) {

            $interval =microtime(true) - $time["start"][$event];
            $time["interval"][$event] =round($interval*1000)."ms";

        } elseif ( ! $stop) {

            $time["start"][$event] =microtime(true);
        }

        return array();
    }

    /**
     * [set_response_code [description]
     * @param  int $response_code [description]
     * @return [type]      [description]
     */
    // HTTPレスポンスコードの設定
    public static function setResponseCode ($response_code) {

        $response_code_list =array(

            // 1xx Informational 情報
            100 =>"Continue",
            101 =>"Switching Protocols",
            102 =>"Processing",

            // 2xx Success 成功
            200 =>"OK",
            201 =>"Created",
            202 =>"Accepted",
            203 =>"Non-Authoritative Information",
            204 =>"No Content",
            205 =>"Reset Content",
            206 =>"Partial Content",
            207 =>"Multi-Status",
            226 =>"IM Used",

            // 3xx Redirection リダイレクション
            300 =>"Multiple Choices",
            301 =>"Moved Permanently",
            302 =>"Found",
            303 =>"See Other",
            304 =>"Not Modified",
            305 =>"Use Proxy",
            306 =>"(Unused)",
            307 =>"Temporary Redirect",

            // 4xx Client Error クライアントエラー
            400 =>"Bad Request",
            401 =>"Unauthorized",
            402 =>"Payment Required",
            403 =>"Forbidden",
            404 =>"Not Found",
            405 =>"Method Not Allowed",
            406 =>"Not Acceptable",
            407 =>"Proxy Authentication Required",
            408 =>"Request Timeout",
            409 =>"Conflict",
            410 =>"Gone",
            411 =>"Length Required",
            412 =>"Precondition Failed",
            413 =>"Request Entity Too Large",
            414 =>"Request-URI Too Long",
            415 =>"Unsupported Media Type",
            416 =>"Requested Range Not Satisfiable",
            417 =>"Expectation Failed",
            418 =>"I'm a teapot",
            422 =>"Unprocessable Entity",
            423 =>"Locked",
            424 =>"Failed Dependency",
            426 =>"Upgrade Required",

            // 5xx Server Error サーバエラー
            500 =>"Internal Server Error",
            501 =>"Not Implemented",
            502 =>"Bad Gateway",
            503 =>"Service Unavailable",
            504 =>"Gateway Timeout",
            505 =>"HTTP Version Not Supported",
            506 =>"Variant Also Negotiates",
            507 =>"Insufficient Storage",
            509 =>"Bandwidth Limit Exceeded",
            510 =>"Not Extended",
        );

        if ($response_msg =$response_code_list[$response_code]) {

            header("HTTP/1.1 ".$response_code." ".$response_msg);

            Vars::registry("Response.response_code",$response_code);

        } else {

            Report::reportError("Invalid Response Code",array(
                "response_code" =>$response_code,
            ));
        }

        if ($error_document =Vars::registry("Config.error_document.".$response_code)) {

            include($error_document);
        }
    }

    /**
     * [redirect [description]
     * @param  string $url [description]
     * @param  array $params [description]
     * @param  string $anchor [description]
     * @return [type]      [description]
     */
    public static function redirect ($url, $params=array(), $anchor=null) {

        if (preg_match('!^page:(.*)$!',$url,$match)) {

            if ($tmp_url =Path::pageToUrl($match[1])) {

                $url =$tmp_url;

            } else {

                Report::reportError("Redirect page is-not routed.",array(
                    "page" =>$match[1],
                ));
            }
        }

        $url =Webapp::applyUrlRewriteRules($url);

        $params =array_merge(
            (array)$params,
            (array)Webapp::outputRewriteVar(),
            (array)Webapp::redirectRewriteVar()
        );

        if (ini_get("session.use_trans_sid")
                && $_REQUEST[session_name()] == session_id()) {

            $params[session_name()] =session_id();
        }

        $url =Html::url($url,$params,$anchor);

        if (Webapp::getWebappDync("report")) {

            $redirect_link_html ='<div style="padding:20px;'
                    .'background-color:#f8f8f8;border:solid 1px #aaaaaa;">'
                    .'Redirect ... '.$url.'</div>';
            print Html::tag("a",array("href"=>$url),$redirect_link_html);

        } else {

            header("Location: ".$url);
        }

        Webapp::shutdownWebapp("redirect",array(
            "url" =>$url,
        ));
    }

    /**
     * [redirect_permanently [description]
     * @param  string $url [description]
     * @param  array $params [description]
     * @param  string $anchor [description]
     * @return [type]      [description]
     */
    public static function redirectPermanently ($url, $params=array(), $flush_message=null) {

        Webapp::setResponseCode(301);

        Webapp::redirect ($url,$params,$flush_message);
    }

    /**
     * [flush_message [description]
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    // FlushMessageの設定/取得
    public static function flushMessage ($flush_message=null) {

        $s_flush_message =& Vars::refSession("flush_message");

        if ($flush_message === false) {

            $s_flush_message =null;

        } elseif ($flush_message) {

            $s_flush_message =$flush_message;
        }

        return $s_flush_message;
    }

    /**
     * [get_webapp_dync [description]
     * @param  [type] $flg [description]
     * @return [type]      [description]
     */
    // 稼働状態の確認
    public static function getWebappDync ($flg="report") {

        // reportのみregistryによる強制ON設定を適用
        if ($flg=="report" && Vars::registry("Report.force_reporting")) {

            return true;
        }

        return $flg && Vars::registry("Config.dync.".$flg);
    }

    /**
     * [get_cli_mode [description]
     * @return [type]      [description]
     */
    // CLI（コマンドライン）実行であるかどうかの確認
    public static function getCliMode () {

        return php_sapi_name() == "cli";
    }

    /**
     * [get_cli_params [description]
     * @return [type]      [description]
     */
    // CLI（コマンドライン）実行時パラメータの取得
    public static function getCliParams () {

        $argv =$_SERVER["argv"];
        unset($argv[0]);

        $params =array();

        foreach ($argv as $a_argv) {

            // --XXX=AAA , --XXX
            if (preg_match('!^--([^=]+)(?:=(.+))?$!',$a_argv,$match)) {

                $params[$match[1]] =$match[2];

            // -X , -XAAA
            } elseif (preg_match('!^-(.)(.+)?$!',$a_argv,$match)) {

                $params[$match[1]] =$match[2];

            // XXX
            } else {

                $params[] =$a_argv;
            }
        }

        return $params;
    }

    /**
     * [label [description]
     * @return [type]      [description]
     */
    // ラベルを得る
    public static function label () {

        $names =func_get_args();
        return (string)Vars::registry("Label.".implode(".",$names));
    }

    /**
     * [check_user_agent [description]
     * @param  [type] $detail [description]
     * @param  [type] $user_agent_string [description]
     * @return [type]      [description]
     */
    // UserAgentの判定
    public static function checkUserAgent ($detail=0, $user_agent_string=null) {

        /*
            [detail arg]:   0  / 1
            iPhone or iPod: sp / iphone
            iPad:           sp / ipad
            Android Phone:  sp / android_phone
            Android Tablet: sp / android_tab
            Softbank:       mb / softbank
            DoCoMo:         mb / docomo
            AU:             mb / au
            Others:         pc / pc
        */

        if ($user_agent_string === null) {

            $user_agent_string =$_SERVER["HTTP_USER_AGENT"];
        }

        $ua_list =array(
            'iphone'        =>array('!iPhone|iPod!',                     'sp'),
            'ipad'          =>array('!iPad!',                            'sp'),
            'android_phone' =>array('!Android.*?Mobile!',                'sp'),
            'android_tab'   =>array('!Android!',                         'sp'),
            'softbank'      =>array('!J-PHONE|Vodafone|MOT-|SoftBank!i', 'mb'),
            'docomo'        =>array('!DoCoMo!i',                         'mb'),
            'au'            =>array('!UP\.Browser|KDDI!i',               'mb'),
        );

        foreach ($ua_list as $k => $v) {

            if (preg_match($v[0],$user_agent_string)) {

                if ($detail == 0) {

                    return $v[1];
                }

                return $k;
            }
        }

        return "pc";
    }
}
