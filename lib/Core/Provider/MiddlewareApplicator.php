<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\InvokableProvider;

class MiddlewareApplicator implements InvokableProvider
{
    public function invoke ($callback, $middleware_config)
    {
        return $this->apply($callback, $middleware_config);
    }
    /**
     * Middlewareの関連づけ
     */
    public function apply ($callback, $middleware_config)
    {
        foreach ((array)$middleware_config as $middleware_name => $check) {
            $result = $check();
            $middleware_callback = null;
            if ( ! $result) {
                continue;
            } else if (is_callable($result)) {
                $middleware_callback = $result;
            } else {
                $middleware = app()->make("middleware.".$middleware_name);
                $middleware_callback = array($middleware,"handler");
            }
            $callback_next = function () use ($callback, $middleware_callback) {
                return call_user_func($middleware_callback,$callback);
            };
            $callback = $callback_next;
        }
        return $callback;
    }
}
