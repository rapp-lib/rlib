<?php
/*
	2016/07/11
		core/report.php内にあるReportErrorクラスの分離・置き換え

 */
namespace R\Lib\Core;

use R\Lib\Core\Webapp;


//-------------------------------------
//
class ReportError extends ErrorException {
	
	protected $report_vars;

	//-------------------------------------
	//
	public static function __construct ($report_vars=array()) {
		
		parent::__construct(
			$report_vars["options"]["errstr"], 
			$report_vars["options"]["code"] ? $params["options"]["code"] : 0, 
			$report_vars["options"]["errno"], 
			$report_vars["options"]["errfile"], 
			$report_vars["options"]["errline"]
		);
		$this->report_vars =$report_vars;
	}

	//-------------------------------------
	//
	public static function shutdown () {
		
		Webapp::shutdownWebapp("error_report",$this->report_vars);
	}