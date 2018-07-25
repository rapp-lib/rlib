<?php
namespace R\Lib\Exception;
use Illuminate\Exception\Handler as IlluminateHandler;
use Symfony\Component\Debug\Exception\FatalErrorException as FatalError;

class Handler extends IlluminateHandler
{
	public function handleException($exception)
	{
        // PHP7以降でThrowableが発行された際にLaravelException/Debugbarともに非対応なのでExceptionに変換
        if ( ! $exception instanceof \Exception) {
            $exception = new FatalError("Exception ".get_class($exception)." : ".$exception->getMessage());
        }
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
	public function handleError($level, $message, $file = '', $line = 0, $context = array())
	{
		if (self::isFatal($level)) {
			throw new \ErrorException($message, 0, $level, $file, $line);
		} elseif (self::isWarning($level)) {
            report_warning("PHP Warning : ".$message, array(
                "file"=>$file, "line"=>$line, "context"=>$context 
            ));
        }
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
	protected function isWarning($type)
	{
        return in_array($type, array(E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING));
	}
}
