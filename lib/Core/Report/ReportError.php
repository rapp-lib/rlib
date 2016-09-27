<?php
/*
    2016/07/11
        core/report.php内にあるReportErrorクラスの分離・置き換え

 */
namespace R\Lib\Core\Report;

use R\Lib\Core\Webapp;

/**
 *
 */
class ReportError extends \ErrorException
{
    protected $report_vars;

    /**
     * [__construct description]
     * @param array $report_vars [description]
     */
    public function __construct ($report_vars=array()) {

        parent::__construct(
            $report_vars["options"]["errstr"],
            $report_vars["options"]["code"] ? $params["options"]["code"] : 0,
            $report_vars["options"]["errno"],
            $report_vars["options"]["errfile"],
            $report_vars["options"]["errline"]
        );
        $this->report_vars =$report_vars;
    }

    /**
     * [shutdown description]
     * @return [type] [description]
     */
    public function shutdown () {

        Webapp::shutdownWebapp("error_report",$this->report_vars);
    }
}