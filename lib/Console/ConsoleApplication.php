<?php
namespace R\Lib\Console;
use Illuminate\Console\Application;

class ConsoleApplication extends Application
{
	public static function start($app)
	{
        if ( ! in_array("-q",$GLOBALS["argv"]) && ! in_array("--quiet",$GLOBALS["argv"])) {
            $app->debug->setDebugLevel(1);
        }
        //$app->setRequestForConsoleEnvironment();
		return parent::start($app);
	}
	public function boot()
	{
        if (file_exists($include_file = $this->laravel['path'].'/bootstrap/artisan.php')) {
            include $include_file;
        }
		if (isset($this->laravel['events'])) {
			$this->laravel['events']->fire('artisan.start', array($this));
		}
		return $this;
	}
    public function renderException($e, $output)
    {
		throw $e;
	}
}
