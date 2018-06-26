<?php
namespace R\Lib\Exception;
use Illuminate\Exception\Handler as IlluminateHandler;
use Symfony\Component\Debug\Exception\FatalErrorException as FatalError;

class Handler extends IlluminateHandler
{
	public function handleException($exception)
	{
        if ( ! app()->config["app.no_cleanup_on_exception"]) ob_end_clean();
        app("events")->fire("app.handle_exception", array($exception));
        if (app()->runningInConsole()) return null;
        return parent::handleException($exception);
    }
    public function handleUncaughtException($exception)
    {
        $response = $this->handleException($exception);
        if ( ! app()->runningInConsole()) app()->http->emit($response);
    }
    public function handleShutdown()
    {
        $error = error_get_last();
        if ( ! is_null($error)){
            extract($error);
            if ( ! $this->isFatal($type)) return;
            $response = $this->handleException(new FatalError($message, $type, 0, $file, $line));
            if ( ! app()->runningInConsole()) app()->http->emit($response);
        }
    }

}
