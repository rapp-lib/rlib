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
        static::$records[] = ReportRenderer::compactRecord($record);
    }
    public function getRecordsByCategory()
    {
        $result = array();
        foreach (static::$records as &$record) {
            $category = $record["context"]["__"]["category"];
            if (strlen($category)) $result[$category][] = $record;
            elseif ($record["level"] >= \Monolog\Logger::ERROR) $result["Error"][] = $record;
            elseif ($record["level"] >= \Monolog\Logger::WARNING) $result["Warning"][] = $record;
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
