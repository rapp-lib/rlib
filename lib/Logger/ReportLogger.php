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
     * ※
     */
    public function registerReportHandler ()
    {
        $logging_handler = new ReportLoggingHandler(Logger::DEBUG);
        $this->pushHandler($logging_handler);
    }
    /**
     * Fatal/Uncaught/エラー/警告を処理するHandler登録
     */
    public function registerErrorHandler ()
    {
        // エラー/Fatalでの停止処理は最後に実行されるように登録（pushHandlerの逆）
        $logging_handler = new ErrorLoggingHandler(Logger::ERROR);
        array_push($this->handlers, $logging_handler);
        // 各ErrorHandlerのregister処理
        $error_handler = new ErrorHandler($this);
        $error_handler->registerExceptionHandler(Logger::CRITICAL);
        $error_handler->registerFatalHandler(Logger::CRITICAL);
        $error_handler->registerErrorHandler(array(), true, E_ERROR|E_WARNING);
    }
    /**
     * SmartyやMailerなどの出力処理中のReport出力抑制/バッファ制御
     * @deprecated
     */
    public function report_buffer_start ()
    {
        $GLOBALS["__REPORT_BUFFER_LEVEL"] += 1;
    }
    /**
     * @deprecated
     */
    public function report_buffer_end ($all=false)
    {
        // 全件終了
        if ($all) {
            $GLOBALS["__REPORT_BUFFER_LEVEL"] = 1;
        }
        // 開始していなければ処理を行わない
        if ($GLOBALS["__REPORT_BUFFER_LEVEL"] > 0) {
            $GLOBALS["__REPORT_BUFFER_LEVEL"] -= 1;
            if ($GLOBALS["__REPORT_BUFFER_LEVEL"] == 0) {
                print $GLOBALS["__REPORT_BUFFER"];
                $GLOBALS["__REPORT_BUFFER"] = "";
            }
        }
    }
}
