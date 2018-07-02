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
        $result = array();
        foreach (static::$records as &$record) {
            $category = $record["context"]["__"]["category"];
            if (strlen($category)) $result[$category][] = $record;
            elseif ($record["level"] >= \Monolog\Logger::WARNING) $result["Error"][] = $record;
            elseif ($record["level"] >= \Monolog\Logger::INFO) $result["Info"][] = $record;
            else $result["Debug"][] = $record;
        }
        return $result;
    }
    public function getCategories()
    {
        $categories = array("Debug", "Error");
        foreach (static::$records as &$record) {
            $category = $record["context"]["__"]["category"];
            if ( ! isset($category)) continue;
            if ( ! in_array($category, $categories)) $categories[] = $category;
        }
        return $categories;
    }
    public function renderHtml($records)
    {
        return ReportRenderer::renderAll($records, "html");
    }
    public function clear()
    {
        self::$records = array();
    }
}
