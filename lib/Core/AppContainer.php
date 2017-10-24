<?php
namespace R\Lib\Core;

class AppContainer
{
    protected $instances = array();
    protected $providers = array(
        // 4.1
        "enum" => 'R\Lib\Enum\EnumRepositry',
        "view" => 'R\Lib\View\ViewFactory',
        // 4.0
        "config" => 'R\Lib\Core\Config',
        "env" => 'R\Lib\Core\Env',
        "debug" => 'R\Lib\Core\Debug',
        "report" => 'R\Lib\Report\ReportDriver',
        "http" => 'R\Lib\Http\HttpDriver',
        "cache" => 'R\Lib\Cache\CacheDriver',
        "session" => 'R\Lib\Session\SessionDriver',
        "user" => 'R\Lib\Auth\UserLoginDriver',
        "file" => 'R\Lib\File\UserFileDriver',
        "db" => 'R\Lib\DBAL\DBDriver',
        // 3.x
        "table" => 'R\Lib\Table\TableFactory',
        "form" => 'R\Lib\Form\FormFactory',
        "console" => 'R\Lib\Console\ConsoleDriver',
        "builder" => 'R\Lib\Builder\WebappBuilder',
        "asset" => 'R\Lib\Asset\AssetManager',
    );
    public function __construct ($providers=array())
    {
        foreach ($providers as $k=>$v) $this->providers[$k] = $v;
        app_set($this);
    }
    public function getProvider ($provider_name)
    {
        if ( ! $this->instances[$provider_name]) {
            // providerインスタンスの生成
            $class = $this->providers[$provider_name];
            if ( ! $class || ! class_exists($class)) {
                report_error("providerクラスが定義されていません",array(
                    "provider_name" => $provider_name,
                    "class" => $class,
                ));
            }
            $this->instances[$provider_name] = new $class();
            // __mountedの呼び出し
            if (method_exists($this->instances[$provider_name], "__mounted")) {
                call_user_func(array($this->instances[$provider_name], "__mounted"), $this);
            }
        }
        return $this->instances[$provider_name];
    }
    public function __get ($provider_name)
    {
        return $this->getProvider($provider_name);
    }
    public function __call ($provider_name, $args)
    {
        // __invokeの呼び出し
        if ( ! method_exists($this->getProvider($provider_name), "__invoke")) {
            report_error("__invokeが定義されていません",array(
                "provider_name" => $provider_name,
            ));
        }
        return call_user_func_array(array($this->getProvider($provider_name), "__invoke"), $args);
    }
}
