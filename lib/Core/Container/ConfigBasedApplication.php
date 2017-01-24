<?php
namespace R\Lib\Core\Container;

use R\Lib\Core\Contract\Container;

class ConfigBasedApplication implements Container
{
    public function init ($init_params)
    {
        // bindの反映
        $this->applyBindConfig($init_params);
        // configの反映
        $this->applyConfigValues($init_params);
        // 終了処理
        set_exception_handler(function($e) {
            if (is_a($e,"R\Lib\Core\Exception\ResponseException")) {
                $response = $e->getResponse();
                $response->render();
            } else {
                app()->response->error("Uncaught. ".get_class($e).": ".$e->getMessage(),array(
                    "exception" =>$e,
                ))->render();
            }
        });
        register_shutdown_function(function() {
            // FatalErrorによる強制終了
            $error = error_get_last();
            if ($error && ($error['type'] == E_ERROR || $error['type'] == E_PARSE
                    || $error['type'] == E_CORE_ERROR || $error['type'] == E_COMPILE_ERROR)) {
                app()->response->error("Fatal Error. ".$error["message"] ,array("error"=>$error) ,array(
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
    public function exec ()
    {
        $callback = app()->config("app.exec");
        if ( ! is_callable($callback)) {
            report_error("設定が不正です", array(
                "key" => "Config.app_exec_callback",
                "value" => $callback,
            ));
        }
        $response = call_user_func($callback, app()->request);
        var_dump($callback);
        return $response;
    }

// -- init_config配列の読み込み

    private function applyBindConfig ($params)
    {
        $config = $this->filterConfig($params["config"], $params["tags"], "bind");
        foreach ($config as $binds) {
            $binds = array_dot($binds);
            foreach ($binds as $name => $class) {
                $this->bind($name, $class);
            }
        }
    }
    private function applyConfigValues ($params)
    {
        $config = $this->filterConfig($params["config"], $params["tags"], "config");
        $result = array();
        foreach ($config as $config_list) {
            foreach ($config_list as $config_values) {
                if (is_callable($config_values)) {
                    $config_values = call_user_func($config_values);
                }
                app()->config($config_values);
            }
        }
    }
    private function filterConfig ($init_config, $search_tags, $search_key)
    {
        $result_config = array();
        foreach ($init_config as $k=>$v) {
            if ($k!==$search_key) {
                $k_parts = explode(":", $k);
                if ( ! ($search_key===$k_parts[0] && in_array($k_parts[1], (array)$search_tags))) {
                    continue;
                }
            }
            $result_config[$k] = $v;
        }
        return $result_config;
    }

// -- インスタンス管理機能の構成

    protected $aliases = array();
    protected $singleton_instances = array();
    /**
     * Singletonインスタンスの取得
     */
    public function bind ($name, $class)
    {
        $this->aliases[$name] = $class;
    }
    /**
     * クラスの探索
     */
    public function find ($class, $required=false)
    {
        if (isset($this->aliases[$class])) {
            $class = $this->aliases[$class];
        }
        if ( ! class_exists($class) && $required) {
            report_error("Classの定義がありません",array(
                "class" => $class,
            ));
        }
        return $class;
    }
    /**
     * Singletonインスタンスの取得
     */
    public function singleton ($class)
    {
        $class = $this->find($class,true);
        if ( ! isset($this->singleton_instances[$class])) {
            if (method_exists($class, "singletonFactory")) {
                $this->singleton_instances[$class] = call_user_func(array($class,"singletonFactory"));
            } else {
                $this->singleton_instances[$class] = new $class();
            }
        }
        return $this->singleton_instances[$class];
    }
    /**
     * インスタンスの作成
     */
    public function make ($class, $constructor_args)
    {
        $class = $this->find($class,true);
        $ref = new \ReflectionClass($class);
        return $ref->newInstanceArgs($constructor_args);
    }

// -- Providerによる機能構成

    /**
     * Providerの登録
     */
    public function bindProvider ($provider_name, $provider_class)
    {
        $this->alias("provider.".$provider_name, $provider_class);
    }
    /**
     * Providerの取得
     */
    protected function getProvider ($provider_name)
    {
        return $this->singleton("provider.".$provider_name);
    }
    /**
     * InvokerProvider::invokeの呼び出し
     */
    public function __call ($provider_name, $args)
    {
        $provider = $this->getProvider($provider_name);
        if ( ! isset($provider)) {
            report_error("Providerが登録されていません",array(
                "provider_name" => $provider_name,
            ));
        }
        return call_user_func_array(array($provider,"invoke"),$args);
    }
    /**
     * Providerの取得
     */
    public function __get ($provider_name)
    {
        $provider = $this->getProvider($provider_name);
        if ( ! isset($provider)) {
            report_error("Providerが登録されていません",array(
                "provider_name" => $provider_name,
            ));
        }
        return $provider;
    }
}
