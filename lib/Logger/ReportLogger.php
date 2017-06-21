<?php
namespace R\Lib\Logger;
use R\Lib\Core\Contract\InvokableProvider;
use Monolog\Logger;
use Monolog\ErrorHandler;

class ReportLogger extends Logger implements InvokableProvider
{
    public function invoke ($msg, $vars=array())
    {
        $this->info($msg, $vars);
    }
    public function __construct ()
    {
        parent::__construct("report");
    }
    /**
     * Report出力するHandler登録
     */
    public function registerReportHandler ()
    {
        $logging_handler = new ReportLoggingHandler(Logger::DEBUG);
        $this->pushHandler($logging_handler);
    }
    /**
     * 停止を伴わないSPL警告などにLogging処理を登録
     */
    public function listenPhpError ()
    {
        class_exists('\Psr\Log\LogLevel', true);
        // 停止を伴わないSPL警告などにLogging処理を登録
        $this->prev_spl_error_handler = set_error_handler(array($this, 'splErrorHandler'), error_reporting());
        // 致命的なエラー時の処理はErrorDriverが実装
        app()->error->onError(array($this, "errorHandler"));
    }
    /**
     * @private
     */
    public function errorHandler($message, $params, $error_options)
    {
        if ($error_options["php_error"]) {
            $level = $this->getPhpErrorCodeLevel($params["php_error"]["type"]);
            app()->log->log($level, $message, $params);
        } else {
            app()->log->error($message, $params);
        }
    }
    /**
     * @private
     */
    public function splErrorHandler($code, $message, $file = '', $line = 0, $context = array())
    {
        if ( ! ($code & app()->error->getHandlablePhpErrorType())) {
            $e = app()->error->convertPhpErrorToHandlableError(array(
                "code" => $code,
                "message" => $message,
                "file" => $file,
                "line" => $line,
                "context" => $context,
            ));
            $this->errorHandler($e->getMessage(), $e->getParams(), $e->getErrorOptions());
        }
        if (is_callable($this->prev_spl_error_handler)) {
            return call_user_func($this->prev_spl_error_handler, $code, $message, $file, $line, $context);
        }
    }
    private function getPhpErrorCodeLevel($code)
    {
        $map = array(
            E_ERROR             => LogLevel::ERROR,
            E_WARNING           => LogLevel::WARNING,
            E_PARSE             => LogLevel::CRITICAL,
            E_NOTICE            => LogLevel::NOTICE,
            E_CORE_ERROR        => LogLevel::CRITICAL,
            E_CORE_WARNING      => LogLevel::CRITICAL,
            E_COMPILE_ERROR     => LogLevel::CRITICAL,
            E_COMPILE_WARNING   => LogLevel::CRITICAL,
            E_USER_ERROR        => LogLevel::ERROR,
            E_USER_WARNING      => LogLevel::WARNING,
            E_USER_NOTICE       => LogLevel::NOTICE,
            E_STRICT            => LogLevel::NOTICE,
            E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_DEPRECATED        => LogLevel::NOTICE,
            E_USER_DEPRECATED   => LogLevel::NOTICE,
        );
        return isset($map[$code]) ? $map[$code] : LogLevel::CRITICAL;
    }
}
