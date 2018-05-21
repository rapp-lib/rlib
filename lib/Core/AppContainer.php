<?php
namespace R\Lib\Core;
use Illuminate\Foundation\Application;

class AppContainer extends Application
{
    public function __construct ()
    {
        app_set($this);
        $this->instance('Illuminate\Container\Container', $this);
        $this->register('R\Lib\Core\AppServiceProvider');
    }
    public function __call ($provider_name, $args)
    {
        return call_user_func_array(array($this[$provider_name], "__invoke"), $args);
    }
}
