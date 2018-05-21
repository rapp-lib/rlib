<?php
namespace R\Lib\Core;

use Illuminate\Events\EventServiceProvider;

class AppService extends ServiceProvider
{
    protected $base_providers = array(
        'R\Lib\Builder\BuilderService',
        'R\Lib\Table\TableService',
    );
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
        "form" => 'R\Lib\Form\FormFactory',
        "console" => 'R\Lib\Console\ConsoleDriver',
    );
    public function register()
    {
        app_set($this->app);
		$this->app->register(new EventServiceProvider($this->app));
        $this->app->bind('request', function($app){ return null; });
        $this->app->bind('path', function($app){ return null; });
        $this->app->bind('exception', function($app){ return null; });
        foreach ($this->base_bindings as $k=>$v) $this->app->singleton($k, $v);
        foreach ($this->base_providers as $v) $this->app->register($v);
        $this->app->report->listenPhpError();
    }
    public function boot()
    {
    }
}
