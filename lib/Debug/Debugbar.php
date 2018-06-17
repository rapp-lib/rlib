<?php
namespace R\Lib\Debug;
use Barryvdh\Debugbar\LaravelDebugbar;
use R\Lib\Debug\DataCollector\ReportCollector;
use DebugBar\Storage\FileStorage;

use Barryvdh\Debugbar\DataCollector\AuthCollector;
use Barryvdh\Debugbar\DataCollector\EventCollector;
use Barryvdh\Debugbar\DataCollector\FilesCollector;
use Barryvdh\Debugbar\DataCollector\LaravelCollector;
use Barryvdh\Debugbar\DataCollector\LogsCollector;
use Barryvdh\Debugbar\DataCollector\QueryCollector;
use Barryvdh\Debugbar\DataCollector\SessionCollector;
use Barryvdh\Debugbar\DataCollector\SymfonyRequestCollector;
use Barryvdh\Debugbar\DataCollector\ViewCollector;
use Barryvdh\Debugbar\Storage\FilesystemStorage;
use DebugBar\Bridge\MonologCollector;
use DebugBar\Bridge\SwiftMailer\SwiftLogCollector;
use DebugBar\Bridge\SwiftMailer\SwiftMailCollector;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;

class Debugbar extends LaravelDebugbar
{
    public function __construct($app = null)
    {
        parent::__construct($app);
        $stack_dir = constant("R_APP_ROOT_DIR")."/tmp/debug/stack";
        $this->setStorage(new \DebugBar\Storage\FileStorage($stack_dir));
    }
    public function modifyResponse($request, $response)
    {
        $app = $this->app;

        if ($app->runningInConsole() || ! $this->isEnabled() || $this->isDebugbarRequest()) {
            return $response;
        }
        // report_info("Http Served", array(
        //     "request_uri" => app("request.fallback")->getUri(),
        //     "input_values" => app("request.fallback")->getInputValues(),
        // ));
        // Inject report info
        // if ( ! $this->app['config']["debug.no_inject_report"]) {
        //     $response = app("report")->rewriteHttpResponse($response);
        // }

        if ($this->isJsonRequest($request)) {
            try {
                $this->sendDataInHeaders(true);
            } catch (\Exception $e) {
                $app['log']->error('Debugbar exception: ' . $e->getMessage());
            }
        } elseif (strpos($response->getHeaderLine('Content-Type'), 'html') === false) {
            try {
                // Just collect + store data, don't inject it.
                $this->collect();
            } catch (\Exception $e) {
                $app['log']->error('Debugbar exception: ' . $e->getMessage());
            }
        } else {
            if (in_array($response->getStatusCode(), array(301,302))) {
                try {
                    $this->stackData();
                    $location = $response->getHeaderLine("location");
                    $response = app()->http->response("html", '<a href="'.$location.'"><div style="padding:20px;'
                        .'background-color:#f8f8f8;border:solid 1px #aaaaaa;">'
                        .'Location: '.$location.'</div></a>');
                } catch (\Exception $e) {
                    $app['log']->error('Debugbar exception: ' . $e->getMessage());
                }
            }
            try {
                $response = $this->injectDebugbarResponse($response);
            } catch (\Exception $e) {
                $app['log']->error('Debugbar exception: ' . $e->getMessage());
            }
        }
        $this->disable();
        return $response;
    }
    public function collect()
    {
        /** @var Request $request */
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

        if ($this->storage !== null) {
            $this->storage->save($this->getCurrentRequestId(), $this->data);
        }

        return $this->data;
    }
    public function addException ($exception)
    {
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

        $body = \R\Lib\Http\ResponseFactory::createBody($content);
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
