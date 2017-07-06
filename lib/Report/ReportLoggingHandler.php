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

// -- 旧実装

    protected function write_OLD(array $record)
    {
        if (app()->debug->getDebugLevel()) {
            $html = ReportRenderer::render($record);
            // Report.buffer_enableによる出力抑止
            if ($this->buffer_level > 0) {
                $this->buffer_content .= $html;
            // CLIエラー出力
            } else if (php_sapi_name()==="cli") {
                app()->console->outputError($html);
            // ブラウザ標準出力
            } else {
                print $html;
            }
        }
    }
    private $buffer_level = 0;
    private $buffer_content = "";
    public function bufferStart()
    {
        $this->buffer_level += 1;
    }
    public function bufferEnd($all=false)
    {
        if ($all) {
            $this->buffer_level = 1;
        }
        if ($this->buffer_level > 0) {
            $this->buffer_level -= 1;
            if ($this->buffer_level == 0 && strlen($this->buffer_content)) {
                if (app()->debug->getDebugLevel()) {
                    print $this->buffer_content;
                }
                $this->buffer_content = "";
            }
        }
    }
}
