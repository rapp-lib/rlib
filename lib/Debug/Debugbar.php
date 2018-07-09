<?php
namespace R\Lib\Debug;
use Barryvdh\Debugbar\LaravelDebugbar;
use DebugBar\Storage\FileStorage;
use R\Lib\Http\ResponseFactory;
use R\Lib\Debug\DataCollector\ReportCollector;
use Barryvdh\Debugbar\DataCollector\EventCollector;
use DebugBar\DataCollector\ExceptionsCollector;

use Barryvdh\Debugbar\DataCollector\FilesCollector;
use DebugBar\Bridge\SwiftMailer\SwiftLogCollector;
use DebugBar\Bridge\SwiftMailer\SwiftMailCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PhpInfoCollector;

class Debugbar extends LaravelDebugbar
{
    public function __construct($app = null)
    {
        parent::__construct($app);
        $stack_dir = constant("R_APP_ROOT_DIR")."/tmp/debug/stack";
        $this->setStorage(new FileStorage($stack_dir));
    }
    public function modifyResponse($request, $response)
    {
        if ($this->app->runningInConsole() || ! $this->isEnabled() || $this->isDebugbarRequest()) {
            return $response;
        }
        try {
            if ($this->isJsonRequest($request)) {
                $this->sendDataInHeaders(true);
            } elseif (in_array($response->getStatusCode(), array(301,302))) {
                if ($this->app["config"]["debug.not_inject_redirect_page"]) {
                    $this->stackData();
                } else {
                    $location = $response->getHeaderLine("location");
                    $response = $this->app->http->response("html",
                        '<a href="'.$location.'"><div style="padding:20px;'
                        .'background-color:#f8f8f8;border:solid 1px #aaaaaa;">'
                        .'Location: '.$location.'</div></a>');
                    $response = $this->injectDebugbarResponse($response);
                }
            } elseif ($this->isHtmlResponse($response)) {
                $response = $this->injectDebugbarResponse($response);
            } else {
                // Just collect + store data, don't inject it.
                $this->collect();
            }
        } catch (\Exception $e) {
            $this->app['log']->error('Debugbar exception: ' . $e->getMessage());
        }
        $this->disable();
        return $response;
    }
    public function collect()
    {
        $request = $this->app['request.fallback'];
        $server_params = $request->getServerParams();
        $this->data = array(
            '__meta' => array(
                'id' => $this->getCurrentRequestId(),
                'datetime' => date('Y-m-d H:i:s'),
                'utime' => microtime(true),
                'method' => $request->getMethod(),
                'uri' => "".$request->getUri()->withoutAuthority(),
                'ip' => $server_params["REMOTE_ADDR"]
            )
        );

        foreach ($this->collectors as $name => $collector) {
            $this->data[$name] = $collector->collect();
        }

        // Remove all invalid (non UTF-8) characters
        array_walk_recursive(
            $this->data,
            function (&$item) {
                if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
            }
        );

        if ($this->getStorage()) {
            $this->storage->save($this->getCurrentRequestId(), $this->data);
        }

        return $this->data;
    }
    public function addException (\Exception $exception)
    {
        // ExceptionCollectorの処理
        if ( ! $this->hasCollector('exceptions')) {
            $exceptionCollector = new ExceptionsCollector();
            $exceptionCollector->setChainExceptions(true);
            $this->addCollector($exceptionCollector);
        }
        return parent::addException($exception);
    }
    public function injectDebugbarResponse($response)
    {
        $content = $response->getBody();
        $renderer = $this->getJavascriptRenderer();

        if ($this->getStorage()) {
            $openHandlerUrl = $this->route('debugbar.open');
            $renderer->setOpenHandlerUrl($openHandlerUrl);
        }

        $renderedContent = $renderer->renderHead() . $renderer->render();

        $pos = strripos($content, '</body>');
        if (false !== $pos) {
            $content = substr($content, 0, $pos) . $renderedContent . substr($content, $pos);
        } else {
            $content = $content . $renderedContent;
        }

        $body = ResponseFactory::createBody($content);
        $response = $response->withBody($body);

        return $response;
    }
    protected $enabled = true;
    public function isEnabled()
    {
        return $this->app->config["app.debug"] && $this->enabled;
    }
    public function disable()
    {
        $this->enabled = false;
    }
    public function isDebugbarRequest()
    {
        return starts_with($this->app['request.fallback']->getUri()->getPageId(), "debugbar.");
    }
    protected function isJsonRequest($request)
    {
        return $this->app['request.fallback']->isAjax() || $this->app['request.fallback']->wantsJson();
    }
    protected function isHtmlResponse($response)
    {
        $content_type = $response->getHeaderLine('Content-Type');
        return $content_type=="" || strpos($content_type, 'html') !== false;
    }
    protected $js_renderer = null;
    public function getJavascriptRenderer($base_url = null, $base_path = null)
    {
        if ($this->js_renderer === null) {
            $this->js_renderer = new JavascriptRenderer($this, $base_url, $base_path);
            $this->js_renderer->setUrlGenerator($this);
            $this->js_renderer->addAssets(array('widget.css'), array('widget.js'),
                constant("R_LIB_ROOT_DIR")."/assets/debugbar/resources/", null);
        }
        return $this->js_renderer;
    }
    public function route($laravel_page_id, $params=array())
    {
        $map = array(
            "debugbar.open"=>"debugbar.open",
            "debugbar.assets.js"=>"debugbar.assets_js",
            "debugbar.assets.css"=>"debugbar.assets_css",
        );
        $page_id = $map[$laravel_page_id] ?: $laravel_page_id;
        return "".$this->app['request.fallback']->getUri()->getWebroot()->uri("id://".$page_id, $params);
    }
}
