<?php
namespace R\Lib\Http;
use R\Lib\Core\Contract\Provider;
use Psr\Http\Message\ServerRequestInterface;

class HttpDriver implements Provider
{
    protected $served_request = null;
    public function serve ($webroot_config, $deligate, $request=array())
    {
        // Webroot作成
        $webroot = is_string($webroot_config) ? $this->webroot($webroot_config) : new Webroot($webroot_config);
        // ServedRequest作成
        if (is_array($request)) {
            $served_request = ServerRequestFactory::fromGlobals($webroot, $request);
        } elseif ($request instanceof ServerRequestInterface) {
            $served_request = ServerRequestFactory::fromServerRequestInterface($webroot, $request);
        }
        if ($this->served_request) {
            report_error("既にRequestのserve処理中です", array(
                "request_uri"=>$served_request->getUri(),
            ));
        }
        // Dispatch処理
        $this->served_request = $served_request;
        $response = $webroot->dispatch($served_request, $deligate);
        $this->served_request = null;
        report("Http Served", array(
            "request_uri"=>$served_request->getUri(),
            "input_values"=>$served_request->getAttribute(InputValues::ATTRIBUTE_INDEX),
            "response"=>$response,
        ));
        return $response;
    }
    public function getServedRequest ()
    {
        return $this->served_request;
    }

// -- Webroot

    protected $webroots = array();
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

// -- Response

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
        if ($type=="redirect") {
            report("Redirect", array("uri"=>$data));
            if (app()->debug->getDebugLevel()) {
                $type = "html";
                $data = '<a href="'.$data.'"><div style="padding:20px;'
                    .'background-color:#f8f8f8;border:solid 1px #aaaaaa;">'
                    .'Redirect ... '.$data.'</div></a>';
            }
        }
        return ResponseFactory::factory($type, $data, $params);
    }
    public function emit ($response)
    {
        if ($response->getStatusCode()==302 || $response->getStatusCode()==301) {
            $response = app()->report->beforeRedirect($response);
        }
        $emitter = new \Zend\Diactoros\Response\SapiEmitter();
        return $emitter->emit($response);
    }
    private function getErrorHtml ($code=500)
    {
        $error_file = constant("R_LIB_ROOT_DIR")."/assets/error".$code.".php";
        return file_exists($error_file) ? include($error_file) : "HTTP Error ".$code;
    }
}
