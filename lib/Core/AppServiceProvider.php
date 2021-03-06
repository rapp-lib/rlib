<?php
namespace R\Lib\Core;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use R\Lib\Exception\ExceptionServiceProvider;
use R\Lib\Log\LogServiceProvider;
use R\Lib\Debug\DebugServiceProvider;

use Illuminate\Foundation\AliasLoader;
use Dotenv\Dotenv;

class AppServiceProvider extends ServiceProvider
{
    protected $base_singletons = array(
        // 5.0
        'url' => '\R\Lib\Http\UrlGenerator',
        'table.resolver' => '\R\Lib\Table\TableResolver',
        'debug' => 'R\Lib\Core\Debug',
        'report' => 'R\Lib\Report\ReportDriver',
        'table' => '\R\Lib\Table\TableFactory',
        "cache" => 'R\Lib\Cache\CacheDriver',
        "session" => 'R\Lib\Session\SessionDriver',
        // 4.1
        "i18n" => 'R\Lib\I18n\I18nDriver',
        "security" => 'R\Lib\Core\Security',
        "enum" => 'R\Lib\Enum\EnumRepositry',
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
        'farm.publish'=>'\R\Lib\Farm\Command\FarmPublishCommand',
    );
    protected $base_providers = array(
        '\R\Lib\Test\TestServiceProvider',
        '\R\Lib\Doc\DocServiceProvider',
        '\R\Lib\View\ViewServiceProvider',
        '\R\Lib\Mail\MailServiceProvider',
        '\R\Lib\Table\TableServiceProvider',
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
            with(new Dotenv(constant("R_APP_ROOT_DIR"),".env"))->overload();
        }
        // bindings設定
        $this->app->singleton('config', 'R\Lib\Core\Config');
        $singletons = (array)$this->app->config['app.singletons'] + $this->base_singletons;
        foreach ($singletons as $k=>$v) $this->app->singleton($k, $v);
        // 環境名を設定
        $this->app->instance('env', $this->app->config["app.env"] ?: "");
        // Debug設定の検証
        $this->app->debug->check();
        // Fallback用requestセットアップ
        $this->app->singleton('request.fallback', function($app){
            if ($app->bound("request")) return $app["request"];
            $webroot = $app->http->webroot("_fallback", array());
            return $app->http->createServerRequest(array(), $webroot);
        });
        // eventセットアップ
        $this->app->register(new EventServiceProvider($this->app));
        // logセットアップ
        $this->app->register(new LogServiceProvider($this->app));
        // filesセットアップ
        $this->app->register(new FilesystemServiceProvider($this->app));
        // エラー停止処理セットアップ
        $this->app->register(new ExceptionServiceProvider($this->app));
        $this->app['exception']->register($this->app["env"]);
        $this->app['exception']->setDebug($this->app['config']['app.debug']);
        // デバッグ処理セットアップ
        $this->app->register(new DebugServiceProvider($this->app));
        // Session自動Start
        // if ( ! $this->app->runningInConsole() && ! $this->app->config["session.prevent_auto_start"]) {
        //     $this->app->session->start();
        // }
        // Aliases設定読み込み
        AliasLoader::getInstance((array)$this->app->config['app.aliases'])->register();
        // Provider登録
        $this->registerDefaultProviders();
    }
    protected function registerDefaultProviders()
    {
        foreach ((array)$this->base_providers as $provider_name) {
            $this->app->register($provider_name);
        }
        foreach ((array)$this->app->config['app.providers'] as $provider) {
            $this->app->register($provider);
        }
        foreach (glob(constant("R_APP_ROOT_DIR").'/app/Service/*/*ServiceProvider.php') as $file) {
            if (preg_match('!(\w+)/(\w+)ServiceProvider\.php$!', $file, $_) && $_[1]==$_[2]) {
                $provider = '\R\App\Service\\'.$_[1].'\\'.$_[1].'ServiceProvider';
                $this->app->register($provider);
            }
        }
        if ($deffered_providers = $this->app->config['app.deffered_providers']) {
            $this->app->config['app.manifest'] = $this->app["path.storage"]."/meta";
            if ( ! file_exists($this->app->config['app.manifest'])) {
                mkdir($this->app->config['app.manifest'], 0775, true);
            }
            $this->app->getProviderRepository()->load($this->app, $deffered_providers);
        }
    }
    public function boot()
    {
        $this->commands($this->base_commands);
        $this->commands((array)$this->app->config["app.commands"]);
    }
}
