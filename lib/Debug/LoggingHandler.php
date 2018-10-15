<?php
namespace R\Lib\Debug;

use R\Lib\Report\ReportRenderer;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class LoggingHandler extends AbstractProcessingHandler
{
    protected static $records = array();
    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        // 記録の制限チェック（メモリ制限）
        $memory_usage = app("memory_usage")->getMemoryUsage();
        $memory_limit = app("memory_usage")->getMemoryLimit();
        $report_memory_limit_factor = app()->config["debug.report_memory_limit_factor"];
        if ( ! strlen($report_memory_limit_factor)) $report_memory_limit_factor = 0.8;
        if ($memory_usage > $memory_limit * $report_memory_limit_factor) {
            $memory_usage_f = app("memory_usage")->formatBytes($memory_usage);
            $record = array(
                "message"=>"[REPORT_MEMORY_LIMIT_OVER:".$memory_usage_f."] ".$record["message"],
                "level"=>$record["level"],
                "context"=>array(),
            );
        }
        // 記録の制限チェック（件数制限）
        $report_count_limit = app()->config["debug.report_count_limit"];
        if ( ! strlen($report_count_limit)) $report_count_limit = 200;
        if (count(static::$records) == $report_count_limit) {
            $record = array(
                "message"=>"[REPORT_COUNT_LIMIT_OVER:".$report_count_limit."] Report log dissabled.",
                "level"=>Logger::ERROR,
                "context"=>array(),
            );
        } elseif (count(static::$records) > $report_count_limit) {
            return;
        }
        // HTTP実行時にはRecordは一旦記録する
        if ( ! app()->runningInConsole()) {
            static::$records[] = ReportRenderer::compactRecord($record);
        // CLI実行時にはオプションに応じてエラー出力する
        } else {
            if ($record["level"] >= Logger::ERROR) $format = "console";
            elseif (in_array('-vvv', $GLOBALS["argv"])) $format = "console";
            elseif (in_array('-vv', $GLOBALS["argv"])) $format = "console_middle";
            elseif (in_array('-v', $GLOBALS["argv"])) $format = "console_short";
            else return;
            $record = ReportRenderer::compactRecord($record);
            $text = ReportRenderer::renderAll(array($record), $format);
            file_put_contents("php://stderr", $text);
        }
    }
    public function getRecordsByCategory()
    {
        $categories = $this->getCategories();
        $result = array();
        foreach (static::$records as &$record) {
            $category = $record["context"]["__"]["category"];
            if ($record["level"] >= \Monolog\Logger::WARNING) {
                $result["Error"][] = $record;
            } elseif (in_array($category, $categories)) {
                $result[$category][] = $record;
            } elseif ( ! strlen($category)) {
                $result["Debug"][] = $record;
            } else {
                $result["Misc"][] = $record;
            }
        }
        return $result;
    }
    public function getCategories()
    {
        $categories = app()->config["debug.categories"] ?: array(
            "Debug", "App", "SQL", "T_Fetch", "T_Alias", "T_Hook");
        $categories[] = "Error";
        $categories[] = "Misc";
        // foreach (static::$records as &$record) {
        //     $category = $record["context"]["__"]["category"];
        //     if ( ! isset($category)) continue;
        //     if ( ! in_array($category, $categories)) $categories[] = $category;
        // }
        return $categories;
    }
    public function renderHtml($records)
    {
        return ReportRenderer::renderAll($records, "html");
    }
    public function renderArray($records)
    {
        return ReportRenderer::renderAll($records, "array");
    }
    public function clear()
    {
        self::$records = array();
    }
}
