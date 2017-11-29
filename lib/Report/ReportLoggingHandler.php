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
                app()->session("Report_Logging")->add("buffer", array($record));
            }
        }
    }
}
