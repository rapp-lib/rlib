<?php
namespace R\Lib\Exception;
use Illuminate\Exception\Handler as IlluminateHandler;
use Symfony\Component\Debug\Exception\FatalErrorException as FatalError;

class Handler extends IlluminateHandler
{
	public function handleException($exception)
	{
        app("events")->fire("app.handle_exception", array($exception));
        return parent::handleException($exception);
    }
    public function handleUncaughtException($exception)
    {
        $response = $this->handleException($exception);
        if ( ! app()->runningInConsole()) app()->http->emit($response);
    }
    public function handleShutdown()
    {
        //app()->report->beforeShutdown();
        $error = error_get_last();
        if ( ! is_null($error)){
            // $error['php_error_code'] = $error['type'];
            // $e = \R\Lib\Report\ReportRenderer::createHandlableError($error);
            // app()->report->logException($e);
            extract($error);
            if ( ! $this->isFatal($type)) return;
            if ( ! app()->runningInConsole()) {
                $response = $this->handleException(new FatalError($message, $type, 0, $file, $line));
                app()->http->emit($response);
            }
        }
    }

}
