<?php
namespace R\Lib\Core;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Zend\Diactoros\Response;

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
    public function runHttp($request=array(), $webroot=false)
    {
        // request
        if (is_array($request)) $request = $this->http->createServerRequest($request, $webroot);
        $this->http->refreshRequest($request);
        // boot
        $this->boot();
        // dispatch
        $response = $this->http->dispatch($this["request"], function($request){
            return $request->getUri()->getPageController()->run($request);
        });
        // emit
        $this->http->emit($response);
    }
    public function prepareRequest($value)
    {
        return $value;
    }
    public function prepareResponse($value)
    {
        if ( ! $value instanceof Response) $value = new Response($value);
        return $value;
    }
}
