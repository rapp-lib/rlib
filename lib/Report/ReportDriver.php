<?php
namespace R\Lib\Report;
use Monolog\Logger;

class ReportDriver
{
    private $logger = null;
    private $logging_handler = null;
    /**
     * LoggerInterfaceの取得
     */
    public function getLogger()
    {
        if ( ! isset($this->logger)) {
            $this->logger = new Logger("rapp");
            $this->logger->pushHandler($this->getLoggingHandler());
        }
        return $this->logger;
    }
    public function getLoggingHandler()
    {
        if ( ! isset($this->logging_handler)) {
            $this->logging_handler = new ReportLoggingHandler(Logger::DEBUG);
        }
        return $this->logging_handler;
    }
    /**
     * HandlableError例外の発行
     */
    public function raiseError ($message, $params=array(), $error_options=array())
    {
        if ( ! $params["__"]["backtraces"]) {
            $params["__"]["backtraces"] = $this->compactBacktrace(debug_backtrace());
        }
        throw new HandlableError($message, $params, $error_options);
    }
    /**
     * 転送前の処理
     */
    public function beforeRedirect($response)
    {
        return $response;
    }

// -- 出力抑止Buffer制御

    public function bufferStart()
    {
        $this->getLoggingHandler()->bufferStart();
    }
    public function bufferEnd($all=false)
    {
        $this->getLoggingHandler()->bufferStart($all);
    }

// -- Error処理系

    protected $reserved_memory = null;
    protected $prev_spl_exception_handler = null;
    protected $prev_spl_error_handler = null;
    /**
     * PHPエラー処理を登録
     */
    public function listenPhpError ()
    {
        // エラー処理時のメモリ確保
        $this->reserved_memory = str_repeat(' ', 1024 * 3);
        class_exists('\Psr\Log\LogLevel', true);
        // error_handler,exception_handler,shutdown_functionを登録
        $this->prev_spl_exception_handler = set_exception_handler(array($this, 'splExceptionHandler'));
        $this->prev_spl_error_handler = set_error_handler(array($this, 'splErrorHandler'), error_reporting());
        register_shutdown_function(array($this, 'splShutdownHandler'));
    }
    /**
     * 例外のロギング
     */
    public function logException(\Exception $e)
    {
        if ( ! $e instanceof HandlableError) {
            $e = $this->convertExceptionToHandlableError($e);
        }
        $message = $e->getMessage();
        $params = $e->getParams();
        $level = Logger::ERROR;
        if ($params["__"]["php_error_code"]) {
            $level = $this->getPhpErrorCodeLevel($params["__"]["php_error_code"]);
        }
        $this->getLogger()->log($level, $message, $params);
    }
    /**
     * @private
     */
    public function splShutdownHandler()
    {
        $this->reserved_memory = null;
        report_buffer_end(true);
        $last_error = error_get_last();
        if ($last_error && $this->isFatalPhpErrorCode($last_error['type'])) {
            $last_error['php_error_code'] = $last_error['type'];
            $error = $this->convertPhpErrorToHandlableError($last_error);
            $this->logException($error);
        }
    }
    /**
     * @private
     */
    public function splExceptionHandler($e)
    {
        $this->logException($e);
        if (is_callable($this->prev_spl_exception_handler)) {
            call_user_func($this->prev_spl_exception_handler, $e);
        }
    }
    /**
     * @private
     */
    public function splErrorHandler($code, $message, $file = '', $line = 0, $context = array())
    {
        if ($code && ! $this->isFatalPhpErrorCode($code)) {
            $e = $this->convertPhpErrorToHandlableError(array(
                "php_error_code" => $code,
                "message" => $message,
                "file" => $file,
                "line" => $line,
                "context" => $context,
            ));
            $this->logException($e);
        }
        if (is_callable($this->prev_spl_error_handler)) {
            return call_user_func($this->prev_spl_error_handler, $code, $message, $file, $line, $context);
        }
    }

// -- Error情報の加工

    private function convertPhpErrorToHandlableError($last_error)
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
    private function convertExceptionToHandlableError($e)
    {
        // backtracesの簡素化
        $backtraces = $this->compactBacktrace($e->getTrace());
        $message = "[PHP Uncaught ".get_class($e)."] ".$e->getMessage();
        $params = array("__"=>array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'uncaught_exception' => get_class($e),
            "backtraces" => $backtraces,
        ));
        return new HandlableError($message, $params);
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
    private function getPhpErrorCodeLevel($code)
    {
        $map = array(
            E_ERROR             => Logger::ERROR,
            E_WARNING           => Logger::WARNING,
            E_PARSE             => Logger::CRITICAL,
            E_NOTICE            => Logger::NOTICE,
            E_CORE_ERROR        => Logger::CRITICAL,
            E_CORE_WARNING      => Logger::WARNING,
            E_COMPILE_ERROR     => Logger::CRITICAL,
            E_COMPILE_WARNING   => Logger::WARNING,
            E_USER_ERROR        => Logger::ERROR,
            E_USER_WARNING      => Logger::WARNING,
            E_USER_NOTICE       => Logger::NOTICE,
            E_STRICT            => Logger::NOTICE,
            E_RECOVERABLE_ERROR => Logger::WARNING,
            E_DEPRECATED        => Logger::NOTICE,
            E_USER_DEPRECATED   => Logger::NOTICE,
        );
        return isset($map[$code]) ? $map[$code] : Logger::CRITICAL;
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
    private function isFatalPhpErrorCode($php_error_code)
    {
        return $php_error_code & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
    }
}
