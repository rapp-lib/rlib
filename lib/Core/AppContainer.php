<?php
namespace R\Lib\Core;
use Illuminate\Foundation\Application;

class AppContainer extends Application
{
    protected $base_bindings = array(
        // 4.1
        "i18n" => 'R\Lib\I18n\I18nDriver',
        "security" => 'R\Lib\Core\Security',
        "enum" => 'R\Lib\Enum\EnumRepositry',
        "view" => 'R\Lib\View\ViewFactory',
        "test" => 'R\Lib\Test\TestDriver',
        "doc" => 'R\Lib\Doc\DocDriver',
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
        // 3.0
        "table" => 'R\Lib\Table\TableFactory',
        "form" => 'R\Lib\Form\FormFactory',
        "console" => 'R\Lib\Console\ConsoleDriver',
        "builder" => 'R\Lib\Builder\WebappBuilder',
    );
    public function __construct ()
    {
		$this->instance('Illuminate\Container\Container', $this);
        foreach ($this->base_bindings as $k=>$v) $this->singleton($k, $v);
    }
    public function __call ($provider_name, $args)
    {
        return call_user_func_array(array($this[$provider_name], "__invoke"), $args);
    }
}
