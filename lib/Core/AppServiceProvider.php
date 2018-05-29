<?php
namespace R\Lib\Core;

use R\Lib\Exception\ExceptionServiceProvider;
use R\Lib\Debug\DebugServiceProvider;
use R\Lib\Farm\FarmServiceProvider;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Log\LogServiceProvider;

use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Config\EnvironmentVariables;
use Dotenv\Dotenv;

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
        'farm.publish'=>'\R\Lib\Farm\Command\FarmPublishCommand',
    );
    public function register()
    {
        // パス設定
        $this->app->instance('path', constant("R_APP_ROOT_DIR")."/app");
        $this->app->instance('path.app', constant("R_APP_ROOT_DIR")."/app");
        $this->app->instance('path.base', constant("R_APP_ROOT_DIR"));
        $this->app->instance('path.storage', constant("R_APP_ROOT_DIR")."/tmp");
        //$this->app->instance('path.public', constant("R_APP_ROOT_DIR")."/public");
        // 環境変数の読み込み
        if (file_exists(constant("R_APP_ROOT_DIR")."/.env")) {
            with(new Dotenv(constant("R_APP_ROOT_DIR"),".env"))->load();
        }
        // lib以下のService登録
        foreach ($this->base_bindings as $k=>$v) $this->app->singleton($k, $v);
        // 環境名を設定
        $this->app->instance('env', "".$this->app->config["app.env"]);
        // Requestの関連付け
        $this->app->bind('request', function($app){
            return $app->http->createServerRequest();
        });
        // Laravel標準Provider登録
        $this->app->register(new EventServiceProvider($this->app));
        // Report起動
        $this->app->register(new LogServiceProvider($this->app));
        $this->app->register(new ExceptionServiceProvider($this->app));
        $this->app['exception']->register($this->app["env"]);
        $this->app['exception']->setDebug($this->app['config']['app.debug']);
        $this->app->register(new DebugServiceProvider($this->app));
        // Timezone設定
        if ($this->app->config['app.timezone']) date_default_timezone_set($this->app->config['timezone']);
        // Aliases設定読み込み
        AliasLoader::getInstance((array)$this->app->config['app.aliases'])->register();
        // Providers設定読み込み
        $this->app->config['app.manifest'] = $this->app["path.storage"]."/meta";
        if ( ! is_writable($this->app->config['app.manifest'])) {
            mkdir($this->app->config['app.manifest'], 0777, true);
        }
        $this->app->getProviderRepository()->load($this->app, (array)$this->app->config['app.providers']);
        // Session自動Start
        if ( ! $this->app->runningInConsole() && $this->app->config["session.auto_start"]) {
            $this->app->session->start();
        }
    }
    public function boot()
    {
        $this->commands($this->base_commands);
        $this->commands((array)$this->app->config["app.commands"]);
    }
}
