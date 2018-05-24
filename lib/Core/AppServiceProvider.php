<?php
namespace R\Lib\Core;

use R\Lib\Exception\ExceptionServiceProvider;
use \R\Lib\Reposer\ReportLoggingHandler;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Config\EnvironmentVariables;
use Dotenv\Dotenv;

use Illuminate\Log\LogServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $base_bindings = array(
        // 5.0
        'config' => 'R\Lib\Core\Config',
        'debug' => 'R\Lib\Core\Debug',
        'report' => 'R\Lib\Report\ReportDriver',
        'builder' => '\R\Lib\Builder\WebappBuilder',
        'table' => '\R\Lib\Table\TableFactory',
        "cache" => 'R\Lib\Cache\CacheDriver',
        "session" => 'R\Lib\Session\SessionDriver',
        // 4.1
        "i18n" => 'R\Lib\I18n\I18nDriver',
        "security" => 'R\Lib\Core\Security',
        "enum" => 'R\Lib\Enum\EnumRepositry',
        "view" => 'R\Lib\View\ViewFactory',
        "test" => 'R\Lib\Test\TestDriver',
        "doc" => 'R\Lib\Doc\DocDriver',
        // 4.0
        "http" => 'R\Lib\Http\HttpDriver',
        "user" => 'R\Lib\Auth\UserLoginDriver',
        "file" => 'R\Lib\File\UserFileDriver',
        "db" => 'R\Lib\DBAL\DBDriver',
        // 3.0
        "form" => 'R\Lib\Form\FormFactory',
    );
    protected $base_commands = array(
        'schema:diff'=>'\R\Lib\Table\Command\SchemaDiffCommand',
        'build:make'=>'\R\Lib\Builder\Command\BuildMakeCommand',
    );
    protected $base_providers = array(
        // 'Illuminate\Foundation\Providers\ArtisanServiceProvider',
        // 'Illuminate\Auth\AuthServiceProvider',
        // 'Illuminate\Cache\CacheServiceProvider',
        // 'Illuminate\Session\CommandsServiceProvider',
        // 'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
        // 'Illuminate\Routing\ControllerServiceProvider',
        // 'Illuminate\Cookie\CookieServiceProvider',
        // 'Illuminate\Database\DatabaseServiceProvider',
        // 'Illuminate\Encryption\EncryptionServiceProvider',
        // 'Illuminate\Filesystem\FilesystemServiceProvider',
        // 'Illuminate\Hashing\HashServiceProvider',
        // 'Illuminate\Html\HtmlServiceProvider',
        'Illuminate\Log\LogServiceProvider',
        // 'Illuminate\Mail\MailServiceProvider',
        // 'Illuminate\Database\MigrationServiceProvider',
        // 'Illuminate\Pagination\PaginationServiceProvider',
        // 'Illuminate\Queue\QueueServiceProvider',
        // 'Illuminate\Redis\RedisServiceProvider',
        // 'Illuminate\Remote\RemoteServiceProvider',
        // 'Illuminate\Auth\Reminders\ReminderServiceProvider',
        // 'Illuminate\Database\SeedServiceProvider',
        // 'Illuminate\Session\SessionServiceProvider',
        // 'Illuminate\Translation\TranslationServiceProvider',
        // 'Illuminate\Validation\ValidationServiceProvider',
        // 'Illuminate\View\ViewServiceProvider',
        'Illuminate\Workbench\WorkbenchServiceProvider',
    );
    public function register()
    {
        // パス設定
        $this->app->instance('path', constant("R_APP_ROOT_DIR")."/app");
        $this->app->instance('path.app', constant("R_APP_ROOT_DIR")."/app");
        $this->app->instance('path.base', constant("R_APP_ROOT_DIR"));
        $this->app->instance('path.storage', constant("R_APP_ROOT_DIR")."/tmp/storage");
        //$this->app->instance('path.public', constant("R_APP_ROOT_DIR")."/public");
        // 環境変数の読み込み
        with(new Dotenv(constant("R_APP_ROOT_DIR")))->load();
        // lib以下のService登録
        foreach ($this->base_bindings as $k=>$v) $this->app->singleton($k, $v);
        // 環境名を設定
        $this->app->instance('env', $this->app->config["app.env"]);
        // Laravel標準Provider登録
        $this->app->register(new EventServiceProvider($this->app));
        $this->app->bind('request', function($app){
            return forward_static_call(array('\Illuminate\Http\Request', 'createFromGlobals'));
        });
        // Report起動
        //$this->app->report->listenPhpError();
		$this->app->register(new ExceptionServiceProvider($this->app));
        $this->app['exception']->register($this->app["env"]);
        $this->app['exception']->setDebug($this->app['config']['app.debug']);
        // Timezone設定
        if ($this->app->config['app.timezone']) date_default_timezone_set($this->app->config['timezone']);
        // Aliases設定読み込み
        AliasLoader::getInstance((array)$this->app->config['app.aliases'])->register();
        // Providers設定読み込み
        $this->app->config['app.manifest'] = $this->app["path.storage"]."/meta";
        $providers = array_merge($this->base_providers, (array)$this->app->config['app.providers']);
        $this->app->getProviderRepository()->load($this->app, $providers);
        if ( ! $this->app->runningInConsole()) {
            // Session自動Start
            if ($this->app->config["session.auto_start"]) $this->app->session->start();
        }
    }
    public function boot()
    {
        $this->commands($this->base_commands);
        $this->commands((array)$this->app->config["app.commands"]);
    }
}
