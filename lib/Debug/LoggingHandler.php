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
    public function getRecordsByLevels()
    {
        $result = array();
        foreach (static::$records as $record) {
            if ($record["level"] >= \Monolog\Logger::ERROR) $result["error"][] = $record;
            elseif ($record["level"] >= \Monolog\Logger::WARNING) $result["warning"][] = $record;
            elseif ($record["level"] >= \Monolog\Logger::INFO) $result["info"][] = $record;
            else $result["debug"][] = $record;
        }
        return $result;
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
