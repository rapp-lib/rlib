<?php
namespace R\Lib\Debug;

/**
 * メモリ消費量の追跡を行い、値に応じてDebug処理を軽量化する
 */
class MemoryUsageTracer
{
    private $last_memory_usage = 0;
    /**
     * メモリ使用量の確認
     */
    public function getMemoryUsage()
    {
        return memory_get_usage();
    }
    /**
     * Byte表記の可読化
     */
    public function formatBytes($value)
    {
        $unit = array('B','KB','MB','GB','TB','PB');
        return round($value/pow(1024,($i=floor(log($value,1024)))),2).' '.$unit[$i];
    }
    /**
     * memory_limitの取得
     */
    public function getMemoryLimit()
    {
        if ( ! isset($this->memory_limit)) {
            $this->memory_limit = PHP_INT_MAX;
            if (preg_match('!^(\d+)(?:(KB?)|(MB?)|(GB?))?!i', ini_get("memory_limit"), $_)) {
                $this->memory_limit = $_[1];
                if ($_[2]) $this->memory_limit *= 1000;
                if ($_[3]) $this->memory_limit *= 1000 * 1000;
                if ($_[4]) $this->memory_limit *= 1000 * 1000 * 1000;
            }
        }
        return $this->memory_limit;
    }

// -- ReportMemoryLimit

    /**
     * Debugが有効に動作するmemory上限の確認
     */
    public function checkReportMemoryLimit()
    {
        return $this->getReportMemoryLimit() > $this->getMemoryUsage();
    }
    /**
     * Debugが有効に動作するmemory上限の取得
     */
    public function getReportMemoryLimit()
    {
        return app()->config["debug.report_memory_limit"] ?: $this->getMemoryLimit()*0.5;
    }
}
