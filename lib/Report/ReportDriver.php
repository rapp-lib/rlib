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
        throw ReportRenderer::createHandlableError(array("message"=>$message, "params"=>$params));
    }

// -- shutdown前の自動出力

    protected $error_shutdown = false;
    protected $auto_flush_before_shutdown = true;
    public function setAutoFlushBeforeShutdown($auto_flush_before_shutdown)
    {
        $this->auto_flush_before_shutdown = $auto_flush_before_shutdown;
    }
    public function beforeShutdown()
    {
        if ($this->error_shutdown) $this->getLoggingHandler()->errorOutputBeforeShutdown();
        if ($this->auto_flush_before_shutdown) $this->getLoggingHandler()->autoFlushBeforeShutdown();
    }

// -- http応答の書き換え

    protected $rewite_http_response = true;
    public function setRewriteHttpResponse($rewite_http_response)
    {
        $this->rewite_http_response = $rewite_http_response;
    }
    public function rewriteHttpResponse($response)
    {
        if ( ! $this->rewite_http_response) return $response;
        return $this->getLoggingHandler()->rewriteHttpResponse($response);
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
    public function logException($e)
    {
        if ( ! $e instanceof HandlableError) {
            $e = ReportRenderer::createHandlableError(array("exception"=>$e));
        }
        $message = $e->getMessage();
        $params = $e->getParams();
        $level = $params["level"];
        $this->getLogger()->log($level, $message, $params);
    }
    /**
     * @private
     */
    public function splShutdownHandler()
    {
        $this->reserved_memory = null;
        $last_error = error_get_last();
        if ($last_error && self::isFatalPhpErrorCode($last_error['type'])) {
            $last_error['php_error_code'] = $last_error['type'];
            $e = ReportRenderer::createHandlableError($last_error);
            $this->logException($e);
            $this->error_shutdown = true;
        }
        $this->beforeShutdown();
    }
    /**
     * @private
     */
    public function splExceptionHandler($e)
    {
        $this->logException($e);
        $this->error_shutdown = true;
        if (is_callable($this->prev_spl_exception_handler)) {
            call_user_func($this->prev_spl_exception_handler, $e);
        }
    }
    /**
     * @private
     */
    public function splErrorHandler($code, $message, $file = '', $line = 0, $context = array())
    {
        if ($code && ! self::isFatalPhpErrorCode($code)) {
            $e = ReportRenderer::createHandlableError(array(
                "php_error_code" => $code,
                "message" => $message,
                "file" => $file,
                "line" => $line,
                "params" => $context,
            ));
            $this->logException($e);
        }
        if (is_callable($this->prev_spl_error_handler)) {
            return call_user_func($this->prev_spl_error_handler, $code, $message, $file, $line, $context);
        }
    }
    public static function isFatalPhpErrorCode($php_error_code)
    {
        return $php_error_code & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
    }
}
