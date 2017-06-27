<?php
namespace R\Lib\Http;
use R\Lib\Core\Contract\Provider;

class HttpDriver implements Provider
{
    protected $served_request = null;
    protected $webroots = array();
    public function __call ($func, $args)
    {
        if ( ! $this->served_request) {
            report_error("事前にRequestをserveする必要があります");
        }
        return call_user_func_array(array($this->served_request, $func), $args);
    }
    public function serve ($webroot_config, $request=array())
    {
        $webroot = new Webroot($webroot_config);
        $served_request = new ServerRequest($webroot, $request);
        $this->setServedRequest($served_request);
        return $served_request;
    }
    public function setServedRequest ($request)
    {
        $this->served_request = $request;
    }
    public function emit ($response)
    {
        $emitter = new \Zend\Diactoros\Response\SapiEmitter();
        return $emitter->emit($response);
    }
    public function response ($type, $response=array())
    {
        //@todo: Responseの組み立て
    }
    public function request ($uri, $request=array())
    {
        //@todo: 外部へのRequestの組み立て
    }
    public function webroot ($webroot_name, $webroot_config=false)
    {
        if ( ! isset($this->webroots[$webroot_name])) {
            if ($webroot_config === false) {
                $webroot_config = app()->config("webroot.".$webroot_name);
            }
            $this->webroots[$webroot_name] = new Webroot($webroot_config);
        } elseif ($webroot_config !== false) {
            report_error("Webrootは初期化済みです");
        }
        return $this->webroots[$webroot_name];
    }
}
