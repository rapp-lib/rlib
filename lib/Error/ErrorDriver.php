<?php
namespace R\Lib\Error;
use R\Lib\Core\Contract\InvokableProvider;
use R\Lib\Error\HandlableError;

class ErrorDriver implements InvokableProvider
{
    public function invoke($message, $params=array(), $error_options=array())
    {
        throw new \R\Lib\Error\HandlableError($message, $params, $error_options);
    }
    protected $error_handlers = array();
    /**
     * エラー時の処理を登録
     */
    public function onError ($handler)
    {
        $this->error_handlers[] = $handler;
    }
    /**
     * エラー時の処理を実行
     */
    public function handleError ($handlable_error)
    {
        foreach ($this->error_handlers as $handler) {
            call_user_func_array($handler,array(
                $handlable_error->getMessage(),
                $handlable_error->getParams(),
                $handlable_error->getErrorOptions(),
            ));
        }
    }
    protected $handlable_php_error_type = 0;
    protected $reserved_memory = null;
    protected $prev_set_exception_handler = null;
    /**
     * PHPエラー時の処理の設定
     */
    public function listenPhpError ()
    {
        // エラー処理時のメモリ確保
        $this->reserved_memory = str_repeat(' ', 1024 * 3);
        $this->handlable_php_error_type = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;
        register_shutdown_function(array($this, 'splShutdownHandler'));
        $this->prev_set_exception_handler = set_exception_handler(array($this, 'splExceptionHandler'));
    }
    /**
     * 処理を行うエラータイプの取得
     */
    public function getHandlablePhpErrorType ()
    {
        return $this->handlable_php_error_type;
    }
    /**
     * @private
     */
    public function splShutdownHandler()
    {
        $this->reserved_memory = null;
        $last_error = error_get_last();
        if ($last_error && $last_error['type'] & $this->getHandlablePhpErrorType()) {
            $last_error['code'] = $last_error['type'];
            $error = $this->convertPhpErrorToHandlableError($last_error);
            $this->handleError($error);
        }
    }
    /**
     * @private
     */
    public function splExceptionHandler($e)
    {
        $message = "[PHP Uncaught ".get_class($e)."] ".$e->getMessage();
        $params = array("uncaught_exception"=>array(
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            "backtraces" => $e->getTrace(),
        ));
        $error = new HandlableError($message, $params, array("uncaught_exception"=>true));
        $this->handleError($error);
        if (is_callable($this->prev_set_exception_handler)) {
            call_user_func($this->prev_set_exception_handler, $e);
        }
    }
    /**
     *
     */
    public function convertPhpErrorToHandlableError($last_error)
    {
        $message = '[PHP '.$this->getPhpErrorCodeText($last_error['code']).'] '.$last_error['message'];
        $params = array("php_error"=>$last_error);
        return new HandlableError($message, $params, array("php_error"=>true));
    }
    private function getPhpErrorCodeText($code)
    {
        $map = array(
            E_ERROR             => "E_ERROR",
            E_WARNING           => "E_WARNING",
            E_PARSE             => "E_PARSE",
            E_NOTICE            => "E_NOTICE",
            E_CORE_ERROR        => "E_CORE_ERROR",
            E_CORE_WARNING      => "E_CORE_WARNING",
            E_COMPILE_ERROR     => "E_COMPILE_ERROR",
            E_COMPILE_WARNING   => "E_COMPILE_WARNING",
            E_USER_ERROR        => "E_USER_ERROR",
            E_USER_WARNING      => "E_USER_WARNING",
            E_USER_NOTICE       => "E_USER_NOTICE",
            E_STRICT            => "E_STRICT",
            E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
            E_DEPRECATED        => "E_DEPRECATED",
            E_USER_DEPRECATED   => "E_USER_DEPRECATED",
        );
        return isset($map[$code]) ? $map[$code] : "UNKNOWN";
    }
}
