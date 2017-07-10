<?php
namespace R\Lib\Report;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class ReportLoggingHandler extends AbstractProcessingHandler
{
    protected function write(array $record)
    {
        if (app()->debug->getDebugLevel()) {
            $record = ReportRenderer::compactRecord($record);
            // CLI→即時エラー出力
            if (php_sapi_name()==="cli") {
                $html = ReportRenderer::render($record, "console");
                app()->console->outputError($html);
            // HttpであればSessionBufferに追加
            } else {
                app()->session("Report_Logging")->buffer[] = $record;
            }
        }
    }

// --

    /**
     * Reportの情報を簡素化する
     */
    public static function simplifyContext ( & $arr)
    {
        foreach ($arr as & $v) {
            if (is_array($v)) {
                self::simplifyContext($v);
            } elseif (is_object($v) && $v instanceof \Closure) {
                $v = "object(".get_class($v).")";
            }
        }
    }
}
