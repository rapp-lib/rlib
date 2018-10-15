<?php
namespace R\Lib\Debug;

/**
 * メモリ消費量の追跡を行う
 * XDebug Garbage Collection Statisticsも検討すべき
 */
class MemoryUsageTracer
{
    private $ref_tracker_enabled = false;
    private $auto_gc_enabled = false;
    private $stack = array();
    private $refs = array();

// -- 動作設定

    /**
     * メモリ使用量の取得時にGCを自動起動するかどうかの設定
     * (注意)取得できるのが実際のメモリ使用量ではなくなります
     * (注意)gc_collect_cycles()の呼び出しにもメモリを使います
     */
    public function setAutoGcEnabled($enabled)
    {
        return $this->auto_gc_enabled = $enabled;
    }
    /**
     * 参照追跡機能の有効設定
     * (注意)この機能が参照を保持するため、参照は解放されなくなる
     */
    public function setRefTrackerEnabled($enabled)
    {
        return $this->ref_tracker_enabled = $enabled;
    }

// -- 計測結果の追跡

    /**
     * TraceStackの取得
     */
    public function getStack()
    {
        return $this->stack;
    }
    /**
     * 手続き間のメモリ使用量の追跡
     */
    public function traceProcedure($label="", $callback=null)
    {
        $start_ref_counts = $this->getRefCounts();
        $start_memory_usage = $this->getMemoryUsage();
        if ($callback) $result = $callback();
        $end_memory_usage = $this->getMemoryUsage();
        $end_ref_counts = $this->getRefCounts();
        if ( ! $callback && count($this->stack)) {
            $last_stack = $this->stack[count($this->stack)-1];
            $start_memory_usage = $last_stack["end_memory_usage"];
            $start_ref_counts = $last_stack["end_ref_counts"];
        }
        if ( ! $label) {
            $label = "@".$this->getCalleeLocation();
        }
        $this->stack[] = array(
            "label"=>$label,
            "start_memory_usage"=>$start_memory_usage,
            "end_memory_usage"=>$end_memory_usage,
            "start_ref_counts"=>$start_ref_counts,
            "end_ref_counts"=>$end_ref_counts,
        );
        return $result;
    }
    /**
     * 変数参照に関する参照数の追跡
     */
    public function addTrackRef($ref, $label="")
    {
        if ($this->ref_tracker_enabled && is_object($ref)) {
            if ( ! $label) {
                $status = $this->getRefStatus($ref);
                $label = sprintf('#%03d (%s) @%s',
                    $status["id"],
                    $status["type"],
                    $this->getCalleeLocation());
            }
            $this->refs[] = array(
                "label"=>$label,
                "ref"=>$ref,
            );
        }
        return $ref;
    }
    /**
     * 追跡中の変数参照に関する参照数の取得
     */
    public function getRefCounts()
    {
        $counts = array();
        foreach ($this->refs as $k=>$v) {
            $status = $this->getRefStatus($v["ref"]);
            $counts[$k] = ($status["refcount"] ?: 1) - 1;
        }
        return $counts;
    }

// -- 計測処理

    /**
     * 変数の参照数の取得
     */
    public function getRefStatus($ref)
    {
        ob_start();
        debug_zval_dump($ref);
        $str = ob_get_clean();
        preg_match('!^(.*?)#(\d+) \(\d+\) refcount\((\d+)\)!', $str, $_);
        return array("type"=>$_[1], "id"=>$_[2], "refcount"=>$_[3]);
    }
    /**
     * メモリ使用量の取得
     */
    public function getMemoryUsage()
    {
        if ($this->auto_gc_enabled) gc_collect_cycles();
        return memory_get_usage();
    }
    /**
     * メモリ使用量の最大値の取得
     */
    public function getMemoryPeakUsage()
    {
        return memory_get_peak_usage();
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

// -- 表記の整形

    /**
     * Byte表記の整形
     */
    public function formatBytes($value)
    {
        $unit = array('B','KB','MB','GB','TB','PB');
        $output = round($value/pow(1024,($i=floor(log(abs($value),1024)))),2).' '.$unit[$i];
        return $output;
    }
    /**
     * TraceStackの整形
     */
    public function formatStack($stack, $format="text")
    {
        $output = "";
        foreach ($stack as $i=>$record) {
            $output .= sprintf("%03d %-30s", $i+1, $record["label"]);
            $output .= sprintf(" MEM (%10s) (+%10s)\n",
                $this->formatBytes($record["start_memory_usage"]),
                $this->formatBytes($record["end_memory_usage"]-$record["start_memory_usage"]));
            if ($record["end_ref_counts"]) foreach ($record["end_ref_counts"] as $j=>$item) {
                $output .= sprintf("    %20s REF %3d (+%3d)\n",
                    $this->refs[$j]["label"],
                    $record["end_ref_counts"][$j],
                    $record["end_ref_counts"][$j]-$record["start_ref_counts"][$j]);
            }
        }
        if ($format === "html") $output = "<pre>".$output."</pre>";
        return $output;
    }
    /**
     * 呼び出し元を取得
     */
    public function getCalleeLocation($shift=0)
    {
        $bts = debug_backtrace();
        $bt = $bts[1+$shift];
        $location = sprintf('%s[%s]', basename($bt["file"]), $bt["line"]);
        return $location;
    }
}
