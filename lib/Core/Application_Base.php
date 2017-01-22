<?php
namespace R\Lib\Core;

class Application_Base
{
    public function init ()
    {
        set_exception_handler(function($e) {
            if (is_a($e,"\\R\\Lib\\Core\\ResponseException")) {
                $response = $e->getResponse();
                $response->render();
            } else {
                app()->response()->error("Uncaught. ".get_class($e).": ".$e->getMessage(),array(
                    "exception" =>$e,
                ))->render();
            }
        });
        register_shutdown_function(function() {
            // FatalErrorによる強制終了
            $error = error_get_last();
            if ($error && ($error['type'] == E_ERROR || $error['type'] == E_PARSE
                    || $error['type'] == E_CORE_ERROR || $error['type'] == E_COMPILE_ERROR)) {
                app()->response()->error("Fatal Error. ".$error["message"] ,array("error"=>$error) ,array(
                    "errno" =>$error['type'],
                    "errstr" =>"Fatal Error. ".$error['message'],
                    "errfile" =>$error['file'],
                    "errline" =>$error['line'],
                ))->render();
            }
        });
        set_error_handler(function($errno, $errstr, $errfile=null, $errline=null, $errcontext=null) {
            report($errstr,$errcontext,array(
                "errno" => $errno,
                "errstr" => $errstr,
                "errfile" => $errfile,
                "errline" => $errline,
            ));
        },error_reporting());
    }

// -- Providerによる機能構成

    protected $providers = array();
    /**
     * Providerメソッドの登録
     * @param array $providers Providerメソッド配列
     */
    public function addProvider ($providers)
    {
        foreach ($providers as $provider_name => $provider_method) {
            $this->providers[$provider_name] = $provider_method;
        }
    }
    /**
     * Providerメソッドの呼び出し
     */
    public function __call ($method_name, $args)
    {
        if (isset($this->providers[$method_name])) {
            return call_user_func_array($this->providers[$method_name], $args);
        } else {
            report_error("Providerが登録されていません",array(
                "provider_name" => $method_name,
                "providers" => $this->providers,
            ));
        }
    }
}
