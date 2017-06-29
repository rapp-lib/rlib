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
    {
        if ( ! $params["__"]["backtraces"]) {
            $params["__"]["backtraces"] = debug_backtrace();
        }
        $params["__"]["backtraces"] = $this->compactBacktrace($params["__"]["backtraces"]);
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
        report_buffer_end(true);
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
            $this->handleError($this->convertExceptionToHandlableError($e));
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
        if ( ! isset($last_error["backtraces"])) {
            $last_error["backtraces"] = debug_backtrace();
        }
        $last_error["backtraces"] = $this->compactBacktrace($last_error["backtraces"]);
        // contextの簡素化
        if (is_array($last_error["context"])) {
            foreach ($last_error["context"] as $k=>$v) {
                if (is_array($v)) {
                    $last_error["context"][$k] = "array(".count($v).")";
                } elseif (is_object($v)) {
                    $last_error["context"][$k] = "object(".get_class($v).")";
                }
            }
        }
        $message = '[PHP '.$this->getPhpErrorCodeText($last_error['php_error_code']).'] '.$last_error['message'];
        return new HandlableError($message, array("__"=>$last_error));
    }
    public function convertExceptionToHandlableError($e)
    {
        // backtracesの簡素化
        $backtraces = $this->compactBacktrace($e->getTrace());
        // handleError実行
        $message = "[PHP Uncaught ".get_class($e)."] ".$e->getMessage();
        $params = array("__"=>array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'uncaught_exception' => get_class($e),
            "backtraces" => $backtraces,
        ));
        return new HandlableError($message, $params);
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
    private function compactBacktrace($backtrace)
    {
        foreach ($backtrace as $k1=>$v1) {
            foreach ((array)$v1["args"] as $k2=>$v2) {
                if (is_array($v2)) {
                    $backtrace[$k1]["args"][$k2] = "array(".count($v2).")";
                } elseif (is_object($v2)) {
                    $backtrace[$k1]["args"][$k2] = "object(".get_class($v2).")";
                }
            }
            unset($backtrace[$k1]["object"]);
        }
        return $backtrace;
    }
}
