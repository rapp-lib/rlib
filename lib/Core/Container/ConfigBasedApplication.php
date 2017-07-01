<?php
namespace R\Lib\Core\Container;

use R\Lib\Core\Contract\Container;

class ConfigBasedApplication implements Container
{
    protected $bind_config = array(
        "provider" => array(
            "http" => 'R\Lib\Http\HttpDriver',
            "report" => 'R\Lib\Report\ReportDriver',

            "console" => 'R\Lib\Core\Provider\ConsoleDriver',
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
            "asset" => 'R\Lib\Asset\AssetManager',
            "session" => 'R\Lib\Core\Provider\Session',
            "mailer" => 'R\Lib\Core\Provider\MailerFactory',
            "auth" => 'R\Lib\Auth\AccountManager',
        ),
        "contract" => array(
            "provider" => array(
                //"router" => 'R\Lib\Route\RouteManager',
            ),
        ),
        "middleware" => array(
        ),
    );
    public function __construct ($bind_config=array())
    {
        $bind_config = array_merge(array_dot($this->bind_config), array_dot($bind_config));
        foreach ($bind_config as $name => $class) {
            $this->bind($name, $class);
        }
    }

// -- インスタンス管理機能の構成

    protected $aliases = array();
    protected $singleton_instances = array();
    /**
     *
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
        return $constructor_args ? $ref->newInstanceArgs($constructor_args) : $ref->newInstance();
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
