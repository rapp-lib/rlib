<?php
/*
アプリケーションの終了
    正常終了
        HTMLテンプレートを処理してWebレスポンスを返す
        JSに対してJSONを返す
        ファイルのダウンロード
        302転送
    異常終了
        report_errorによる半強制停止
            400/500などのレスポンスコードを返して終了
        FatalErrorによる強制終了
        UncaughtExceptionによる終了
        Smartyテンプレートの処理中のエラー
            SmartyErrorExceptionが強制発行されて、

response()->report_buffer()->write($msg, $values);
response()->shutdown(array(
    "error" => true,
    "error" => true,
));
 */

    // 終了処理の登録
    register_shutdown_webapp_function(function($cause, $options){
        if ($cause == "error_report") {
            // 異常停止のログを記録
            // cat /var/log/httpd/error_log| grep "RAPP_ERROR" | less
            $msg ="";
            $msg .="RAPP_ERROR ";
            $msg .=($_SERVER["HTTPS"] ? "https" : "http")."://".$_SERVER["SERVER_NAME"]."] : ";
            $msg .=registry("Request.request_uri");
            $msg .=report_template($options["errstr"],$options["params"],
                    $options["options"],$options["backtraces"],array("output_format"=>"plain"));
            $msg .=" | Request = ".decorate_value($_REQUEST,false);
            error_log($msg,0);

            set_response_code(500);
        }

        report("WebappLog",array(
            "RequestPath" =>registry("Request.request_path"),
            "RequestPage" =>registry("Request.request_page"),
            "Template" =>registry("Response.template_file"),
            "ShutdownCause" =>$cause,
            "Elapsed" =>elapse(),
            "ResponseState" =>registry("Response.controller_obj"),
        ));
    });

    // Ajaxr応答への変換処理の登録
    if ($_SERVER["HTTP_X_AJAXR"] || $_REQUEST["__ajaxr"]) {
        register_shutdown_webapp_function("shutdown_webapp_for_ajaxr");
    }

    // リクエストURLの取得
    registry("Request.request_uri",$GLOBAS["__BOOTSTRAP_CONFIG__"]["REQUEST_URI"]);

    elapse("webapp.setup");

    // HTTPパラメータ構築
    $request_values =array_merge($_GET,$_POST);
    ob_start(function($out) { return mb_convert_encoding($out,"UTF-8","UTF-8"); });
    mb_convert_variables("UTF-8","UTF-8",$request_values);
    $request_values =sanitize($request_values);

    // 終了処理
    set_error_handler(function($errno, $errstr, $errfile=null, $errline=null, $errcontext=null) {
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
    },E_ALL);
    set_exception_handler(function($e) {
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
    });
    register_shutdown_function(function() {
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
    });

    if ( ! get_cli_mode()) {
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

        // Dync機能の有効化
        if ($dync_key=registry("Config.dync_key")) {
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
            }
        }
        obj("Rdoc")->check();
    }


    // アセットディレクトリの登録
    $assets_path = "/.assets/lib";
    asset()->registerAssetsDirUrl(path_to_file($assets_path), path_to_url($assets_path));
    $assets_path = "/.assets/app";
    asset()->registerAssetsDirUrl(path_to_file($assets_path), path_to_url($assets_path));

    // LRAの処理
    obj("LayoutRequestArray")->fetch_request_array();

    // リクエスト情報の解決
    $request_uri =registry("Request.request_uri");
    $document_root_dir =registry("Path.document_root_dir");
    $html_dir =registry("Path.html_dir");

    $request_path =url_to_path($request_uri, "index.html");
    $request_file =path_to_file($request_path);
    list($request_page, $ext_path, $ext_params) =path_to_page($request_path,true);

    // 静的ページのStaticControllerへの対応付け
    if ( ! $request_page && file_exists($request_file)) {
        $request_page ="static.index";
    }

    // Routing設定もなくHTMLファイルもない場合
    if ( ! $request_page && ! file_exists($request_file)) {

        // 画像処理機能
        if ($request_path=="/img/resize/index.html") {
            $cache_file =obj("ResizeImage")->resize_by_request(array(
                "file_url" =>$_REQUEST["f"],
                "format" =>$_REQUEST["s"].($_REQUEST["t"] ? "-t" : ""),
            ));
            clean_output_shutdown(array("file"=>$cache_file));
            shutdown_webapp("normal");

        // 404エラー
        } else {
            report_warning("Request Trouble: Route and File NotFound",registry("Request"));
            set_response_code(404);
            shutdown_webapp("notfound");
        }
    }

    // 動的パス埋め込みパラメータの解決
    if ($request_path != $ext_path) {
        $request_file =path_to_file($ext_path);
        $request_path =$ext_path;
        array_registry($_REQUEST,$ext_params);
    }

    list($controller_name, $action_name) =explode('.',$request_page,2);
    registry(array(
        "Request.request_file" =>$request_file,
        "Request.request_path" =>$request_path,
        "Request.request_page" =>$request_page,
        "Request.controller_name" => $controller_name,
        "Request.action_name" => $action_name,
    ));

    // レスポンスの設定
    $request_file =registry("Request.request_file");
    $response_charset =registry("Config.external_charset");
    registry("Response.template_file", $request_file);
    registry("Response.content_type", 'text/html; charset='.$response_charset);

    elapse("webapp.setup",true);
    elapse("webapp.raise_action");

    // ControllerActionの取得
    list($controller_name, $action_name) =explode('.',$request_page,2);
    $controller_class_name =str_camelize($controller_name)."Controller";
    $action_method_name ="act_".$action_name;
    if ( ! class_exists($controller_class_name)) {
        report_error("Request Routing Error: Controller/Action raise error",registry("Request"));
    }
    $controller_obj =new $controller_class_name($controller_name,$action_name,$options);
    registry("Response.controller_obj", $controller_obj);
    // 認証
    $controller_obj->authenticate();
    // Action呼び出し
    if (is_callable(array($controller_obj,$action_method_name))) {
        $controller_obj->before_act();
        $controller_obj->$action_method_name();
        $controller_obj->after_act();
    }

    elapse("webapp.raise_action",true);
    elapse("webapp.fetch_template");

    // テンプレートファイルの読み込み
    $template_file =registry("Response.template_file");
    $output =$controller_obj->fetch($template_file);

    // 出力
    $content_type =registry("Response.content_type");
    header("Content-type: ".$content_type);
    print($output);

    elapse("webapp.fetch_template",true);

    shutdown_webapp("normal");
