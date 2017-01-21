<?php
namespace R\Lib\Core;

class Application
{
    private static $app = null;
    private $running = null;
    private $initialized = false;
    /**
     * Applicationインスタンスを取得
     */
    public static function getInstance ()
    {
        if ( ! isset(self::$app)) {
            if (class_exists($app_class = "R\\App\\Application")) {
                self::$app = new $app_class;
            } elseif (class_exists($app_class = "R\\Lib\\Core\\Application")) {
                self::$app = new $app_class;
            }
            self::$app->init();
        }
        return self::$app;
    }
    /**
     * アプリケーションの開始
     * @param  string $start_method 開始時に呼び出すメソッド名
     * @param  string $end_method   終了時に呼び出すメソッド名
     */
    public function start ($start_method, $end_method)
    {
        if (isset($this->running)) {
            report_error("既にアプリケーションが起動しています");
            return;
        }
        $this->running = array(
            "start_method" => $start_method,
            "end_method" => $end_method,
        );
        try {
            if (isset($this->running["start_method"])) {
                $start_method = $this->running["start_method"];
                unset($this->running["start_method"]);
                call_user_func(array($this, $start_method));
            }
            $this->end();
        } catch (ApplicationEndingException $e) {
        }
        unset($this->running);
    }
    /**
     * アプリケーションの終了
     */
    public function end ()
    {
        if (isset($this->running)) {
            if (isset($this->running["end_method"])) {
                $end_method = $this->running["end_method"];
                unset($this->running["end_method"]);
                call_user_func(array($this, $end_method));
            }
            if ( ! $this->running["ending"]) {
                throw new ApplicationEndingException();
            }
        }
    }
    /**
     * アプリケーションの初期化
     */
    public function init ()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        // Composer未対応クラスの互換読み込み処理
        spl_autoload_register("load_class");
        // 終了処理
        set_error_handler(function($errno, $errstr, $errfile=null, $errline=null, $errcontext=null) {
            report($errstr,$errcontext,array(
                "errno" =>$errno,
                "errstr" =>$errstr,
                "errfile" =>$errfile,
                "errline" =>$errline,
            ));
        },error_reporting());
        set_exception_handler(function($e) {
            app()->ending();
            report("[Uncaught ".get_class($e)."] ".$e->getMessage(),array(
                "exception" =>$e,
            ),array(
                "errno" =>E_ERROR,
                "exception" =>$e,
            ));
        });
        register_shutdown_function(function() {
            app()->ending();
            // FatalErrorによる強制終了
            $error = error_get_last();
            if ($error && ($error['type'] == E_ERROR || $error['type'] == E_PARSE
                || $error['type'] == E_CORE_ERROR || $error['type'] == E_COMPILE_ERROR)) {
                try {
                    report("[Fatal] ".$error["message"] ,$error ,array(
                        "type" =>"error_handler",
                        "errno" =>$error['type'],
                        "errstr" =>"Fatal Error. ".$error['message'],
                        "errfile" =>$error['file'],
                        "errline" =>$error['line'],
                    ));
                } catch (ApplicationEndingException $e) {
                }
            }
        });
    }
    /**
     * アプリケーションが終了準備中になったことを設定
     */
    public function ending ()
    {
        $this->running["ending"] = true;
    }
    /**
     * 設定の読み書き
     */
    public function config ($key)
    {
        if (is_null($this->config)) {
            $this->config = new \R\Lib\Core\Configure();
        }
        return $this->config->config($key);
    }
    /**
     * @singleton
     */
    public function request ()
    {
        if ( ! isset($this->request)) {
            $this->request = new \R\Lib\Webapp\Request();
        }
        return $this->request;
    }
    /**
     * @singleton
     */
    public function response ()
    {
        if ( ! isset($this->response)) {
            $this->response = new \R\Lib\Webapp\Response();
        }
        return $this->response;
    }
    /**
     * @factory
     */
    public function session ($key)
    {
        return new \R\Lib\Webapp\Session($key);
    }
    /**
     * APP_ROOT_DIRの取得
     */
    public function getAppRootDir ()
    {
        return $this->config("Path.webapp_dir");
    }
    /**
     * TMP_DIRの取得
     */
    public function getTmpDir ()
    {
        return $this->config("Path.tmp_dir");
    }
    /**
     * デバッグモードの取得
     */
    public function getDebugLevel ()
    {
        return $this->config("Config.debug_level");
    }
    /**
     * アクセスを確認
     */
    public function isDevClient ()
    {
        return defined("DEV_HOSTS") ? util("ServerVars")->ipCheck(constant("DEV_HOSTS")) : true;
    }
}