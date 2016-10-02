<?php

    //-------------------------------------
    //
    function start_webapp () {

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

            if (registry($k) === null) {

                registry($k,$v);
            }
        }

        // php.ini設定
        foreach ((array)registry("Config.php_ini") as $k => $v) {

            ini_set($k, $v);
        }

        // HTTPパラメータ構築
        $_REQUEST =array_merge($_GET,$_POST);

        // 入出力文字コード変換
        ob_start("mb_output_handler_impl");
        mb_convert_variables(
                registry("Config.internal_charset"),
                registry("Config.external_charset"),
                $_REQUEST);
        $_REQUEST =sanitize($_REQUEST);

        // PHPの設定書き換え
        spl_autoload_register("load_class");
        set_error_handler("std_error_handler",E_ALL);
        set_exception_handler('std_exception_handler');
        register_shutdown_function('std_shutdown_handler');

        if ( ! get_cli_mode()) {

            // session_start
            call_user_func(registry("Config.session_start_function"));

            // Dync機能の有効化
            start_dync();
        }

        // include_pathの設定
        foreach ((array)registry("Config.webapp_include_path") as $k => $v) {

            add_include_path(registry("Path.webapp_dir")."/".$v);
        }

        // ライブラリの読み込み
        foreach ((array)registry("Config.load_lib") as $k => $v) {

            load_lib($v);
        }

        obj("Rdoc")->check();
    }

    //-------------------------------------
    // std_session_start
    function std_session_start () {

        // セッションの開始
        $session_lifetime =registry("Config.session_lifetime");
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

    //-------------------------------------
    // start_dync
    function start_dync () {

        if ($dync_key =registry("Config.dync_key")) {

            $dync =(array)$_SESSION["__dync"];

            $sec =$_REQUEST["__ts"];
            $min =floor(time()/60);
            $sec_list =array();

            foreach (range(-5,5) as $i) {

                $sec_list[$i] =encrypt_string(substr(md5($dync_key."/".($min+$i)),12,12));
            }

            if ($_REQUEST[$dync_key] && $sec && (in_array($sec, $sec_list))) {

                $dync["auth"] =$dync_key;
            }

            if ($dync["auth"]) {

                $dync =array_merge($dync,(array)$_REQUEST[$dync_key]);

                $_SESSION["__dync"] =$dync;

                registry("Config.dync",$dync);

                if ($dync["report"]) {

                    //ini_set("display_errors",true);
                    //ini_set("error_reporting",registry("Report.error_reporting"));
                }
            }

            if (registry("Report.report_about_dync")) {

                report("Dync status-report.",array(
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

    //-------------------------------------
    // ob_filter
    function mb_output_handler_impl ($html) {

        $html =mb_convert_encoding(
                $html,
                registry("Config.external_charset"),
                registry("Config.internal_charset"));

        return $html;
    }

    //-------------------------------------
    // 出力と同時に終了
    function clean_output_shutdown ($output) {

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
        $clean_output_buffer =& ref_globals("clean_output_buffer");

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

            echo json_encode((array)$output["json"]);

        // file
        } else if (is_array($output) && $output["file"]) {

            readfile($output["file"]);
        }

        // 以降の出力をバッファに転送
        ob_start("send_to_clean_output_buffer");

        shutdown_webapp("clean_output");
    }

    //-------------------------------------
    // clean_output_shutdown以降の出力をバッファに転送するob処理
    function send_to_clean_output_buffer ($output) {

        $clean_output_buffer =& ref_globals("clean_output_buffer");

        $clean_output_buffer .=$output;

        return "";
    }

    //-------------------------------------
    // ajaxrレスポンスへの出力変換を行うshutdown_webapp_function
    function shutdown_webapp_for_ajaxr ($cause, $options) {

        $res =array();

        $clean_output_buffer =& ref_globals("clean_output_buffer");

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

            if (get_webapp_dync("report")) {

                $res["message"] =$options["errstr"];

            } else {

                $res["message"] ="ERROR";
            }
        } else if ($cause=="normal") {

            $res["response"] =& $clean_output_buffer;
        }

        if (get_webapp_dync("report") && $cause!="normal") {

            $res["report"] ="on";
            $res["buffer"] =& $clean_output_buffer;
        }

        print json_encode($res);
    }

    //-------------------------------------
    // [Deprecated] SEO的に無差別にURLを書き換えることは問題が大きいため非推奨 151003
    // URL書き換え対象のパラメータ追加
    function output_rewrite_var ($name=null, $value=null) {

        $output_rewrite_var =& ref_globals("output_rewrite_var");
        $result =array_registry($output_rewrite_var,$name,$value);

        if ($value !== null) {

            output_add_rewrite_var($name,$value);

            registry('State.is_url_rewrited',true);
        }

        return $result;
    }

    //-------------------------------------
    // [Deprecated] 使用されていないため削除予定 151003
    // 転送時に引き継ぐパラメータの設定
    function redirect_rewrite_var ($name=null, $value=null) {

        $rewrite_var =& ref_globals("redirect_rewrite_var");
        $result =array_registry($rewrite_var,$name,$value);

        return $result;
    }

    //-------------------------------------
    // URL書き換え規則の追加
    function add_url_rewrite_rule ($patterns, $var_name, $value, $info=array()) {

        $url_rewrite_rules =& ref_globals("url_rewrite_rules");
        $url_rewrite_rules[] =array($patterns, $var_name, $value, $info);
    }

    //-------------------------------------
    // URL書き換え規則の適用
    function apply_url_rewrite_rules ($url) {

        $url_rewrite_rules =& ref_globals("url_rewrite_rules");

        $path =url_to_path($url);
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

        return url($url, $params);
    }

    //-------------------------------------
    // 処理を停止するexit相当の機能/異常終了を正しく通知できる
    function shutdown_webapp ($cause=null, $options=array()) {

        if (defined("WEBAPP_SHUTDOWN_CAUSE")) {

            return;
        }

        define("WEBAPP_SHUTDOWN_CAUSE",$cause);

        // 通常終了時はFlushMessageを削除
        if ($cause == "normal") {

            flush_message(false);
        }

        // register_shutdown_webapp_functionで登録された処理の実行
        $funcs =& ref_globals('shutdown_webapp_function');

        foreach ((array)$funcs as $func) {

            call_user_func_array($func,array(
                $cause,
                $options
            ));
        }

        exit;
    }

    //-------------------------------------
    // 全PHP処理終了時に呼び出す関数の設定
    function register_shutdown_webapp_function ($func) {

        $funcs =& ref_globals('shutdown_webapp_function');
        $funcs[] =$func;
    }

    //-------------------------------------
    // 標準PHP終了ハンドラ
    function std_shutdown_handler () {

        report_buffer_end(true);

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

                report_warning("Illegal shutdown, Not routed shutdown_webapp");
            }
        }
    }

    //-------------------------------------
    // 実行実時間の計測
    function elapse ($event=null,$stop=false) {

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

    //-------------------------------------
    // HTTPレスポンスコードの設定
    function set_response_code ($response_code) {

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

            registry("Response.response_code",$response_code);

        } else {

            report_error("Invalid Response Code",array(
                "response_code" =>$response_code,
            ));
        }

        if ($error_document =registry("Config.error_document.".$response_code)) {

            include($error_document);
        }
    }

    //-------------------------------------
    //
    function redirect ($url, $params=array(), $anchor=null) {

        if (preg_match('!^page:(.*)$!',$url,$match)) {

            if ($tmp_url =page_to_url($match[1])) {

                $url =$tmp_url;

            } else {

                report_error("Redirect page is-not routed.",array(
                    "page" =>$match[1],
                ));
            }
        }

        $url =apply_url_rewrite_rules($url);

        $params =array_merge(
            (array)$params,
            (array)output_rewrite_var(),
            (array)redirect_rewrite_var()
        );

        if (ini_get("session.use_trans_sid")
                && $_REQUEST[session_name()] == session_id()) {

            $params[session_name()] =session_id();
        }

        $url =url($url,$params,$anchor);

        if (get_webapp_dync("report")) {

            $redirect_link_html ='<div style="padding:20px;'
                    .'background-color:#f8f8f8;border:solid 1px #aaaaaa;">'
                    .'Redirect ... '.$url.'</div>';
            print tag("a",array("href"=>$url),$redirect_link_html);

        } else {

            header("Location: ".$url);
        }

        shutdown_webapp("redirect",array(
            "url" =>$url,
        ));
    }

    //-------------------------------------
    //
    function redirect_permanently ($url, $params=array(), $flush_message=null) {

        set_response_code(301);

        redirect ($url,$params,$flush_message);
    }

    //-------------------------------------
    // FlushMessageの設定/取得
    function flush_message ($flush_message=null) {

        $s_flush_message =& ref_session("flush_message");

        if ($flush_message === false) {

            $s_flush_message =null;

        } elseif ($flush_message) {

            $s_flush_message =$flush_message;
        }

        return $s_flush_message;
    }

    //-------------------------------------
    // 稼働状態の確認
    function get_webapp_dync ($flg="report") {

        // reportのみregistryによる強制ON設定を適用
        if ($flg=="report" && registry("Report.force_reporting")) {

            return true;
        }

        return $flg && registry("Config.dync.".$flg);
    }

    //-------------------------------------
    // CLI（コマンドライン）実行であるかどうかの確認
    function get_cli_mode () {

        return php_sapi_name() == "cli";
    }

    //-------------------------------------
    // CLI（コマンドライン）実行時パラメータの取得
    function get_cli_params () {

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



    //-------------------------------------
    // ラベルを得る
    function label () {

        $names =func_get_args();
        return (string)registry("Label.".implode(".",$names));
    }

    //-------------------------------------
    // UserAgentの判定
    function check_user_agent ($detail=0, $user_agent_string=null) {

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

    /**
     * @facade R\Lib\Auth\AccountManager::load
     */
    function auth ($name=null)
    {
        return R\Lib\Auth\AccountManager::load($name);
    }

    /**
     * @facade R\Lib\Query\TableFactory::factory
     */
    function table ($table_name, $config=array())
    {
        return R\Lib\Query\TableFactory::factory($table_name, $config);
    }

    /**
     * @facade R\Lib\Builder\WebappBuilder::getSchema
     */
    function builder ()
    {
        return R\Lib\Builder\WebappBuilder::getSchema();
    }
