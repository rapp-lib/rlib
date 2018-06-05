<?php
namespace R\Lib\Http;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Support\Facades\Facade;
use Zend\Diactoros\Response\SapiEmitter;

class HttpDriver
{
    public function getRequest ()
    {
        return app("request");
    }
    public function refreshRequest ($request)
    {
        app()->instance("request", $request);
		Facade::clearResolvedInstance('request');
    }
    public function createServerRequest ($request=array(), $webroot=false)
    {
        // Webroot作成
        if ( ! $webroot) $webroot = $_ENV["APP_WEBROOT"];
        if ( ! $webroot) report_error("webrootが特定できません");
        if (is_string($webroot)) $webroot = $this->webroot($webroot);
        // ServedRequest作成
        $method_name = is_array($request) ? "fromGlobals" : "fromServerRequestInterface";
        return ServerRequestFactory::$method_name($webroot, $request);
    }

// --

    public function dispatch ($request, $next)
    {
        try {
            if (app()->isDownForMaintenance()){
                $response = app('events')->until('illuminate.app.down');
                if ( ! is_null($response)) return app()->prepareResponse($response, $request);
            }
            $stack = $request->getUri()->getWebroot()->getMiddlewareStack();
            $stack[] = $next;
            $dispatcher = new \mindplay\middleman\Dispatcher($stack);
            $response = $dispatcher->dispatch($request);
        } catch (\Exception $e) {
            if (app()->runningUnitTests()) throw $e;
            $response = app("exception")->handleException($e);
        } catch (\Throwable $e) {
            if (app()->runningUnitTests()) throw $e;
            $response = app("exception")->handleException($e);
        }
        report_info("Http Served", array(
            "request_uri" => $request->getUri(),
            "input_values" => $request->getAttribute(InputValues::ATTRIBUTE_INDEX),
        ));
        return $response;
    }

// -- serve

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
        // ErrorFallback
        try {
            // Dispatch処理
            $response = $webroot->dispatch($served_request, $deligate);
        } catch (\Exception $e) {
            if (app()->runningUnitTests()) throw $e;
            $response = app("exception")->handleException($e);
        } catch (\Throwable $e) {
            if (app()->runningUnitTests()) throw $e;
            $response = app("exception")->handleException($e);
        }
        report_info("Http Served", array(
            "request_uri"=>$this->served_request->getUri(),
            "input_values"=>$this->served_request->getAttribute(InputValues::ATTRIBUTE_INDEX),
        ));
        $this->served_request = array_pop($this->served_request_stack);
        return $response;
    }
    public function getServedRequest ()
    {
        return $this->served_request ?: $this->getRequest();
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
        return ResponseFactory::factory($type, $data, $params);
    }
    public function emit ($response)
    {
        // Inject report info
        $response = app()->report->rewriteHttpResponse($response);
        return with($emitter = new SapiEmitter())->emit($response);
    }
}
