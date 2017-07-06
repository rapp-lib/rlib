<?php
namespace R\Lib\Report;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class ReportLoggingHandler extends AbstractProcessingHandler
{
    protected function write(array $record)
    {
        if (app()->debug->getDebugLevel()) {
            self::simplifyRecordContext($record["context"]);
            // CLI→即時エラー出力
            if (php_sapi_name()==="cli") {
                $html = ReportRenderer::render($record);
                app()->console->outputError($html);
            // HttpであればSessionBufferに追加
            } else {
                if ( ! $record["context"]["__"]["backtraces"]) {
                    $record["context"]["__"]["backtraces"] = debug_backtrace();
                }
                app()->session("Report_Logging")->buffer[] = $record;
            }
        }
    }
    private static function simplifyRecordContext( & $arr)
    {
        foreach ($arr as & $v) {
            if (is_array($v)) {
                self::simplifyRecordContext($v);
            } elseif ( is_object($v) && ($v instanceof Closure)) {
                $v = "Closure";
            }
        }
    }
}
