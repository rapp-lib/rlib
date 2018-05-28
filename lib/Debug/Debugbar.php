<?php
namespace R\Lib\Debug;

use Barryvdh\Debugbar\LaravelDebugbar;
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
    public function modifyResponse($request, $response)
    {
        $app = $this->app;
        if ($app->runningInConsole() or !$this->isEnabled() || $this->isDebugbarRequest()) {
            return $response;
        }

        if ($this->shouldCollect('config', true)) {
            try {
                $configCollector = new ConfigCollector();
                $configCollector->setData($app['config']->getItems());
                $this->addCollector($configCollector);
            } catch (\Exception $e) {
                $this->addException(
                    new Exception(
                        'Cannot add ConfigCollector to Laravel Debugbar: ' . $e->getMessage(),
                        $e->getCode(),
                        $e
                    )
                );
            }
        }

        /** @var \Illuminate\Session\SessionManager $sessionManager */
        // $sessionManager = $app['session'];
        // $httpDriver = new SymfonyHttpDriver($sessionManager, $response);
        // $this->setHttpDriver($httpDriver);

        if ($this->shouldCollect('session')) {
            try {
                $this->addCollector(new SessionCollector($sessionManager));
            } catch (\Exception $e) {
                $this->addException(
                    new Exception(
                        'Cannot add SessionCollector to Laravel Debugbar: ' . $e->getMessage(),
                        $e->getCode(),
                        $e
                    )
                );
            }
        }
        if (in_array($response->getStatusCode(), array(301,302))) {
            try {
                $this->stackData();
            } catch (\Exception $e) {
                $app['log']->error('Debugbar exception: ' . $e->getMessage());
            }
        } elseif (
            $this->isJsonRequest($request) and
            $app['config']->get('laravel-debugbar::config.capture_ajax', true)
        ) {
            try {
                $this->sendDataInHeaders(true);
            } catch (\Exception $e) {
                $app['log']->error('Debugbar exception: ' . $e->getMessage());
            }
        } elseif (
            strpos($response->getHeaderLine('Content-Type'), 'html') === false
        ) {
            try {
                // Just collect + store data, don't inject it.
                $this->collect();
            } catch (\Exception $e) {
                $app['log']->error('Debugbar exception: ' . $e->getMessage());
            }
        } elseif ($app['config']->get('laravel-debugbar::config.inject', true)) {
            try {
                $response = $this->injectDebugbar($response);
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
        $request = $this->app['request'];
        $server_params = $request->getServerParams();
        $this->data = array(
            '__meta' => array(
                'id' => $this->getCurrentRequestId(),
                'datetime' => date('Y-m-d H:i:s'),
                'utime' => microtime(true),
                'method' => $request->getMethod(),
                'uri' => "".$request->getUri(),
                'ip' => $server_params["REMOTE_HOST"]
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
    public function injectDebugbar($response)
    {
        $content = $response->getBody();

        $renderer = $this->getJavascriptRenderer();
        if ($this->getStorage()) {
            $openHandlerUrl = $this->app['url']->route('debugbar.openhandler');
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
    protected function isDebugbarRequest()
    {
        return starts_with($this->app['request']->getUri()->getPageId(), "debugbar.");
    }
    protected function isJsonRequest($request)
    {
        return $this->app['request']->isAjax() || $this->app['request']->wantsJson();
    }
    protected $js_renderer = null;
    public function getJavascriptRenderer($base_url = null, $base_path = null)
    {
        if ($this->js_renderer === null) {
            $webroot = $this->app["request"]->getUri()->getWebroot();
            $this->js_renderer = new JavascriptRenderer($this, $base_url, $base_path);
            $this->js_renderer->setUrlGenerator($this);
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
        return $this->app['request']->getUri()->getWebroot()->uri("id://".$page_id, $params);
    }
}
