<?php
namespace R\Lib\Http;
use Psr\Http\Message\ServerRequestInterface;

class HttpDriver
{
    protected $served_request = null;
    protected $served_request_stack = array();
    public function serve ($webroot_name, $deligate, $request=array())
    {
        // Webroot作成
        $webroot = $this->webroot($webroot_name);
        // ServedRequest作成
        if (is_array($request)) {
            $served_request = ServerRequestFactory::fromGlobals($webroot, $request);
        } elseif ($request instanceof ServerRequestInterface) {
            $served_request = ServerRequestFactory::fromServerRequestInterface($webroot, $request);
        }
        array_push($this->served_request_stack, $this->served_request);
        $this->served_request = $served_request;
        // Dispatch処理
        $response = $webroot->dispatch($served_request, $deligate);
        report_info("Http Served", array(
            "request_uri"=>$this->served_request->getUri(),
            "input_values"=>$this->served_request->getAttribute(InputValues::ATTRIBUTE_INDEX),
        ));
        $this->served_request = array_pop($this->served_request_stack);
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
    public function getWebroots ()
    {
        $webroots = array();
        // 設定から未構成分を補完
        foreach ((array)app()->config("http.webroots") as $webroot_name => $webroot_config) {
            if ( ! $this->webroots[$webroot_name]) {
                $this->webroot($webroot_name, $webroot_config);
            }
        }
        return $this->webroots;
    }
    public function getWebrootByPageId ($page_id)
    {
        foreach ($this->getWebroots() as $webroot_name => $webroot) {
            if ($webroot->getRouter()->getRouteByPageId($page_id)) {
                return $webroot;
            }
        }
        return null;
    }

// -- Response

    public function response ($type, $data=null, $params=array())
    {
        if ($error_html = $this->getErrorHtml($type)) {
            $type = "html";
            $data = $error_html;
            report_info("Respond ".$type);
        }
        if ($type=="redirect") {
            report_info("Respond Redirect", array("uri"=>$data));
        }
        return ResponseFactory::factory($type, $data, $params);
    }
    public function emit ($response)
    {
        $response = app()->report->beforeEmitResponse($response);
        $emitter = new \Zend\Diactoros\Response\SapiEmitter();
        return $emitter->emit($response);
    }
    private function getErrorHtml ($type)
    {
        $error_codes = array(
            "badrequest" => 400,
            "forbidden" => 403,
            "notfound" => 404,
            "error" => 500,
        );
        if ( ! $error_codes[$type]) return false;
        $error_file = constant("R_APP_ROOT_DIR")."/error/".$type.".php";
        if ( ! file_exists($error_file)) {
            $error_file = constant("R_LIB_ROOT_DIR")."/assets/error/".$type.".php";
        }
        if (file_exists($error_file)) {
            ob_start();
            include($error_file);
            return ob_get_clean();
        }
        return false;
    }
}
