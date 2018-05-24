<?php
namespace R\Lib\Exception;
use Illuminate\Exception\Handler as IlluminateHandler;
use Symfony\Component\Debug\Exception\FatalErrorException as FatalError;

class Handler extends IlluminateHandler
{
    public function handleUncaughtException($exception)
    {
        $response = $this->handleException($exception);
        app()->http->emit($response);
    }
    public function handleShutdown()
    {
        app()->report->beforeShutdown();
        $error = error_get_last();
        if ( ! is_null($error)){
            extract($error);
            if ( ! $this->isFatal($type)) return;
            $response = $this->handleException(new FatalError($message, $type, 0, $file, $line));
            app()->http->emit($response);
        }
    }

}
