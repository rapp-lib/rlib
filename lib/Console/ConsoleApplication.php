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
        $app->setRequestForConsoleEnvironment();
		return parent::start($app);
	}
}
