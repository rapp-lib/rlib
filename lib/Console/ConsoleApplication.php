<?php
namespace R\Lib\Console;
use Illuminate\Console\Application;

class ConsoleApplication extends Application
{
	public static function start($app)
	{
		return parent::start($app);
	}
	public function boot()
	{
        if (file_exists($include_file = $this->laravel['path'].'/bootstrap/artisan.php')) {
            include $include_file;
        }
		if (isset($this->laravel['events'])) {
			$_push = method_exists(app("events"),"push") ? "push" : "fire";
			$this->laravel['events']->$_pish('artisan.start', array($this));
		}
		return $this;
	}
    public function renderException($e, $output)
    {
		throw $e;
	}
}
