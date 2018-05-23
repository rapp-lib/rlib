<?php
namespace R\Lib\Core;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;

class AppContainer extends Application
{
    public function __construct ()
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this);
        $this->instance('Illuminate\Container\Container', $this);
    }
    public function __call ($provider_name, $args)
    {
        return call_user_func_array(array($this[$provider_name], "__invoke"), $args);
    }
}
