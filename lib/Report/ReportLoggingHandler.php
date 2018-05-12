<?php
namespace R\Lib\Report;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class ReportLoggingHandler extends AbstractProcessingHandler
{
    protected static $buffer = array();
    protected static $buffer_stash_status = "init";
    /**
     * 終了前の処理
     */
    public function autoFlushBeforeShutdown()
    {
        if (app()->debug->getDebugLevel()) {
            // cliの場合は常に終了時にflush
            if (php_sapi_name()==="cli") {
                $this->flush();
            // http応答時はhtml出力の場合のみflush
            } else {
                $content_type = false;
                foreach (headers_list() as $header) {
                    if (preg_match('!^content-type:\s*(\S+)$!i', $header, $_)) {
                        $content_type = $_[1];
                        break;
                    }
                }
                if (preg_match('!^html/text!',$content_type) || ! $content_type) $this->flush();
                else $this->pushStash();
            }
        }
    }
    /**
     * Http応答の書き換え処理
     */
    public function rewriteHttpResponse($response)
    {
        if (app()->debug->getDebugLevel()) {
            if (preg_match('!^text/html!', $response->getHeaderLine('content-type'))) {
                $this->flush();
            } elseif ($response->getStatusCode()==302 || $response->getStatusCode()==301) {
                $this->flush();
                $location = $response->getHeaderLine("location");
                return app()->http->response("html", '<a href="'.$location.'"><div style="padding:20px;'
                    .'background-color:#f8f8f8;border:solid 1px #aaaaaa;">'
                    .'Location: '.$location.'</div></a>');
            }
        }
        return $response;
    }
    /**
     * ログ1件の書き込み
     */
    protected function write(array $record)
    {
        if (app()->debug->getDebugLevel()) {
            $this->popStash();
            static::$buffer[] = ReportRenderer::compactRecord($record);
        }
    }
    /**
     * stashへの待避
     */
    private function pushStash()
    {
        // stashへの待避
        if (php_sapi_name()!=="cli" && app()->session->sessionExists()) {
            if (self::$buffer_stash_status==="open") {
                app()->session("Report_Logging")->add("buffer", self::$buffer);
                self::$buffer = array();
                self::$buffer_stash_status = "close";
            }
        }
    }
    /**
     * stashからの復帰
     */
    private function popStash()
    {
        if (php_sapi_name()!=="cli" && app()->session->sessionExists()) {
            if (self::$buffer_stash_status==="init") {
                $stash_buffer = & app()->session("Report_Logging")->getRef("buffer");
                if ($stash_buffer) foreach ($stash_buffer as $stash_record) {
                    self::$buffer[] = $stash_record;
                }
                $stash_buffer = array();
                self::$buffer_stash_status = "open";
            }
        }
    }
    /**
     * 出力
     */
    private function flush()
    {
        if (php_sapi_name()==="cli") {
            $text = ReportRenderer::renderAll(self::$buffer, "console");
            app()->console->outputError($text);
        } else {
            $html = ReportRenderer::renderAll(self::$buffer, "html");
            print $html;
        }
        self::$buffer = array();
    }
}
