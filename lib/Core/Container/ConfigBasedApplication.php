<?php
namespace R\Lib\Core\Container;

use R\Lib\Core\Contract\Container;

class ConfigBasedApplication implements Container
{
    public function init ($init_params)
    {
        $this->applyBindConfig($init_params);
        $this->applyConfigValues($init_params);
        install_report();
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
        $response = call_user_func($callback);
        return $response;
    }

// -- init_config配列の読み込み

    private function applyBindConfig ($params)
    {
        $base_binds = array(
            "middleware" => array(
                "auth" => 'R\Lib\Auth\Middleware\AuthCheck',
                "view_response_fallback" => 'R\Lib\Core\Middleware\ViewResponseFallback',
                "json_response_fallback" => 'R\Lib\Core\Middleware\JsonResponseFallback',
            ),
            "provider" => array(
                "router" => 'R\Lib\Route\RouteManager',
                "route" => 'R\Lib\Route\RouteManager',
                "config" => 'R\Lib\Core\Provider\Configure',
                "env" => 'R\Lib\Core\Provider\Env',
                "view" => 'R\Lib\View\SmartyViewFactory',
                "table" => 'R\Lib\Table\TableFactory',
                "form" => 'R\Lib\Form\FormFactory',
                "enum" => 'R\Lib\Enum\EnumFactory',
                "file_storage" => 'R\Lib\FileStorage\FileStorageManager',
                "builder" => 'R\Lib\Builder\WebappBuilder',
                "util" => 'R\Lib\Core\Provider\UtilLoader',
                "extention" => 'R\Lib\Core\Provider\ExtentionLoader',
                "debug" => 'R\Lib\Core\Provider\DebugDriver',
                "auth" => 'R\Lib\Auth\AccountManager',
                "asset" => 'R\Lib\Asset\AssetManager',
                "report" => 'R\Lib\Core\Provider\ReportDriver',
                "response" => 'R\Lib\Core\Provider\ResponseFactory',
                "request" => 'R\Lib\Core\Provider\Request',
                "session" => 'R\Lib\Core\Provider\Session',
            ),
            "contract" => array(
                "provider" => array(
                    "router" => 'R\Lib\Route\RouteManager',
                ),
            ),
            "response" => 'R\Lib\Core\Response\HttpResponse',
        );
        $base_binds = array_dot($base_binds);
        $config = $this->filterConfig($params["config"], $params["tags"], "bind");
        foreach ($config as $binds) {
            $binds = array_dot($binds);
            foreach ($binds as $name => $class) {
                $base_binds[$name] = $class;
            }
        }
        foreach ($base_binds as $name => $class) {
            $this->bind($name, $class);
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
    public function find ($class_find, $required=false)
    {
        if ( ! isset($this->aliases[$class_find]) && $required) {
            report_error("クラスが登録されていません : ".$class_find,array(
                "class_find" => $class_find,
            ));
        }
        return $this->aliases[$class_find];
    }
    /**
     * Singletonインスタンスの取得
     */
    public function singleton ($class_find)
    {
        $class = $this->find($class_find, true);
        if ( ! isset($this->singleton_instances[$class])) {
            if ( ! $this->hasContract($class, $class_find)) {
                report_warning("クラス契約が不正です",array(
                    "class" => $class,
                    "class_find" => $class_find,
                    "contract_class" => $this->aliases["contract.".$class_find],
                ));
                return null;
            }
            $this->singleton_instances[$class] = new $class();
        }
        return $this->singleton_instances[$class];
    }
    /**
     * インスタンスの作成
     */
    public function make ($class_find, $constructor_args=array())
    {
        $class = $this->find($class_find,true);
        if ( ! $this->hasContract($class, $class_find)) {
            report_warning("クラス契約が不正です",array(
                "class" => $class,
                "class_find" => $class_find,
                "contract_class" => $this->aliases["contract.".$class_find],
            ));
            return null;
        }
        $ref = new \ReflectionClass($class);
        return $ref->newInstanceArgs($constructor_args);
    }
    /**
     * Contractを持つかどうか確認
     */
    public function hasContract ($class, $contract_name, $required=false)
    {
        $class = is_object($class) ? get_class($class) : $class;
        $contract_class = $this->aliases["contract.".$class];
        if ( ! $contract_class) {
            return true;
        }
        $result = is_subclass_of($class, $contract_class) || $class === $contract_class;
        return $result;
    }

// -- Providerによる機能構成

    /**
     * Providerの登録
     */
    public function bindProvider ($provider_name, $provider_class)
    {
        $this->bind("provider.".$provider_name, $provider_class);
    }
    /**
     * Providerの取得
     */
    public function getProvider ($provider_name)
    {
        return $this->singleton("provider.".$provider_name);
    }
    /**
     * Providerの登録確認
     */
    public function hasProvider ($provider_name)
    {
        return $this->find("provider.".$provider_name);
    }
    /**
     * InvokableProvider::invokeの呼び出し
     */
    public function __call ($provider_name, $args)
    {
        $provider = $this->getProvider($provider_name);
        if ( ! method_exists($provider,"invoke")) {
            report_error("Provider::invokeの定義がありません",array(
                "provider_name" => $provider_name,
                "class" => get_class($provider),
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
        return $provider;
    }
}
