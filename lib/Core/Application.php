<?php
namespace R\Lib\Core;

class Application
{
    private static $app = null;

    private $running = null;

    /**
     * Applicationインスタンスを取得
     */
    public static function getInstance ()
    {
        if ( ! isset(self::$app)) {
            if (class_exists($app_class = "R\\App\\Application")) {
                self::$app = new $app_class;
            } elseif (class_exists($app_class = "R\\Lib\\Core\\App\\Application_Base")) {
                self::$app = new $app_class;
            }
        }
        return self::$app;
    }
    /**
     * アプリケーションの初期化
     */
    public function init ()
    {
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
     * アプリケーションが終了準備中になったことを設定
     */
    public function ending ()
    {
        $this->running["ending"] = true;
    }
    public function config ($key)
    {
        return config($key);
    }
    public function getDebugLevel ()
    {
        return registry("Report.force_reporting") || registry("Config.dync.report") ? 1 : false;
    }
}