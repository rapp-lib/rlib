<?php
namespace R\Lib\Error;
use R\Lib\Core\Contract\InvokableProvider;
use R\Lib\Error\HandlableError;

class ErrorDriver implements InvokableProvider
{
    public function invoke($message, $params=array(), $error_options=array())
    {
        $this->raise($message, $params, $error_options);
    }
    /**
     * HandlableErrorの発行
     */
    public function raise($message, $params=array(), $error_options=array())
    {report("#");
        if ( ! $params["__"]["backtraces"]) {
            $params["__"]["backtraces"] = debug_backtrace();
        }
        throw new HandlableError($message, $params, $error_options);
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
    public function handleError ($e)
    {
        foreach ($this->error_handlers as $handler) {
            call_user_func_array($handler,array(
                $e->getMessage(),
                $e->getParams(),
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
            $last_error['php_error_code'] = $last_error['type'];
            $error = $this->convertPhpErrorToHandlableError($last_error);
            $this->handleError($error);
        }
    }
    /**
     * @private
     */
    public function splExceptionHandler($e)
    {
        if ($e instanceof HandlableError) {
            $this->handleError($e);
        } else {
            $message = "[PHP Uncaught ".get_class($e)."] ".$e->getMessage();
            $params = array("__"=>array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                "backtraces" => $e->getTrace(),
                'uncaught_exception' => $e,
            ));
            $error = new HandlableError($message, $params);
            $this->handleError($error);
        }
        if (is_callable($this->prev_set_exception_handler)) {
            call_user_func($this->prev_set_exception_handler, $e);
        }
    }
    /**
     *
     */
    public function convertPhpErrorToHandlableError($last_error)
    {
        $message = '[PHP '.$this->getPhpErrorCodeText($last_error['php_error_code']).'] '.$last_error['message'];
        if ( ! isset($last_error["backtraces"])) {
            $last_error["backtraces"] = debug_backtrace();
        }
        return new HandlableError($message, array("__"=>$last_error));
    }
    private function getPhpErrorCodeText($php_error_code)
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
        return isset($map[$php_error_code]) ? $map[$php_error_code] : "UNKNOWN";
    }
}
