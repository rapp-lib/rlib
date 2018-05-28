<?php
namespace R\Lib\Debug;
use R\Lib\Http\HttpController;
use DebugBar\OpenHandler;

class DebugbarController extends HttpController
{
    public function act_open()
    {
        $debugbar = app('debugbar');
        if (!$debugbar->isEnabled()) {
            $this->app->abort('500', 'Debugbar is not enabled');
        }
        $openHandler = new OpenHandler($debugbar);
        $data = $openHandler->handle(null, false, false);
        $response = app()->http->response("data", $data, array("headers"=>array(
            'Content-Type' => 'application/json'
        )));
    }
    public function act_assets_js()
    {
        $renderer = app('debugbar')->getJavascriptRenderer();
        $content = $renderer->dumpAssetsToString('js');
        $response = app()->http->response("data", $content, array("headers"=>array(
            'Content-Type' => 'text/javascript',
        )));
        return $this->cacheResponse($response);
    }
    public function act_assets_css()
    {
        $renderer = app('debugbar')->getJavascriptRenderer();
        $content = $renderer->dumpAssetsToString('css');
        $response = app()->http->response("data", $content, array("headers"=>array(
            'Content-Type' => 'text/css',
        )));
        return $this->cacheResponse($response);
    }
    /**
     * Cache the response 1 year (31536000 sec)
     */
    protected function cacheResponse($response)
    {
        $response->withMaxAge(31536000);
        $response->withMaxAge(new \DateTime('+1 year'));
        return $response;
    }
}
