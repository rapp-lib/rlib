<?php
namespace R\Lib\Core;

use Illuminate\Events\EventServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $base_bindings = array(
        'builder' => '\R\Lib\Builder\WebappBuilder',
        'table' => '\R\Lib\Table\TableFactory',
        // 4.1
        "i18n" => 'R\Lib\I18n\I18nDriver',
        "security" => 'R\Lib\Core\Security',
        "enum" => 'R\Lib\Enum\EnumRepositry',
        "view" => 'R\Lib\View\ViewFactory',
        "test" => 'R\Lib\Test\TestDriver',
        "doc" => 'R\Lib\Doc\DocDriver',
        // 4.0
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
        $this->app->instance('path', constant("R_APP_ROOT_DIR"));
        $this->app->singleton('env', 'R\Lib\Core\Env');
        $this->app->singleton('config', 'R\Lib\Core\Config');
        $this->app->singleton('debug', 'R\Lib\Core\Debug');
        $this->app->singleton('report', 'R\Lib\Report\ReportDriver');
        $this->app->report->listenPhpError();
		$this->app->register(new EventServiceProvider($this->app));
        $this->app->bind('request', function($app){ return null; });
        $this->app->bind('exception', function($app){ return null; });
        foreach ($this->base_bindings as $k=>$v) $this->app->singleton($k, $v);
    }
    public function boot()
    {
        $this->commands(array(
            'schema:diff'=>'\R\Lib\Table\Command\SchemaDiffCommand',
            'build:make'=>'\R\Lib\Builder\Command\BuildMakeCommand',
        ));
        $this->commands((array)app()->config("app.commands"));
    }
}
