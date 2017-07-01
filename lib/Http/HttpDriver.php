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
        $webroot = is_string($webroot_config) ? $this->webroot($webroot_config) : new Webroot($webroot_config);
        $served_request = new ServerRequest($webroot, $request);
        $this->setServedRequest($served_request);
        report("Http Served Request",array("request"=>$served_request));
        return $served_request;
    }
    public function emit ($response)
    {
        if ($response->getStatusCode()==302 || $response->getStatusCode()==301) {
            app()->report->beforeRedirect($response);
        }
        $emitter = new \Zend\Diactoros\Response\SapiEmitter();
        return $emitter->emit($response);
    }
    public function response ($type, $data=null, $params=array())
    {
        if ($type=="error" && $error_html = $this->getErrorHtml(500)) {
            $type = "html";
            $data = $error_html;
        }
        if ($type=="notfound" && $error_html = $this->getErrorHtml(404)) {
            $type = "html";
            $data = $error_html;
        }
        return ResponseFactory::factory($type, $data, $params);
    }
    public function request ($uri, $request=array())
    {
        //@todo: 外部へのRequestの組み立て
    }

// -- 非常用機能

    public function webroot ($webroot_name, $webroot_config=false)
    {
        if ( ! isset($this->webroots[$webroot_name])) {
            if ($webroot_config === false) {
                $webroot_config = app()->config("http.webroots.".$webroot_name);
            }
            if ( ! is_array($webroot_config)) {
                report_error("Webrootの構成が不正です",array(
                    "webroot_name" => $webroot_name,
                ));
            }
            $this->webroots[$webroot_name] = new Webroot($webroot_config);
        } elseif ($webroot_config !== false) {
            report_error("Webrootは初期化済みです");
        }
        return $this->webroots[$webroot_name];
    }
    public function setServedRequest ($request)
    {
        $this->served_request = $request;
    }
    public function getServedRequest ()
    {
        return $this->served_request;
    }
    public function getErrorHtml ($code=500)
    {
        $error_file = constant("R_LIB_ROOT_DIR")."/assets/error".$code.".php";
        return file_exists($error_file) ? include($error_file) : "HTTP Error ".$code;
    }
}
