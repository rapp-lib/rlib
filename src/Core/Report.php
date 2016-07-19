<?php
/*
	2016/07/11
		core/report.php内の全関数の移行開始

 */
namespace R\Lib\Core;

use R\Lib\Core\Vars;
use R\Lib\Core\Webapp;
use R\Lib\Core\ReportError;
/**
 * 
 */
class Report {

	/**
	 * [std_error_handler description] 
	 * @param  [type] $errno [description]
	 * @param  [type] $errstr [description]
	 * @param  [type] $errfile [description]
	 * @param  [type] $errline [description]
	 * @param  [type] $errcontext [description]
	 * @return [type]      [description]
	 */
	// 標準レポートハンドラ
	public static function stdErrorHandler (
			$errno, 
			$errstr, 
			$errfile=null, 
			$errline=null, 
			$errcontext=null) {
		
		if ( ! (Webapp::getWebappDync("report") 
				&& (Vars::registry("Report.error_reporting") & $errno)
				&& (error_reporting()!==0))) {
			
			return; 
		}

		Report::report($errstr,$errcontext,array(
			"type" =>"error_handler",
			"errno" =>$errno,
			"errstr" =>$errstr,
			"errfile" =>$errfile,
			"errline" =>$errline,
		));
	}

	/**
	 * [std_exception_handler description] 
	 * @param  [type] $e [description]
	 * @return [type]      [description]
	 */
	// 標準例外ハンドラ
	public static function stdExceptionHandler ($e) {
	
		try {

			if (is_a($e,"ReportError")) {

				throw $e;
			}

			Report::report("[".get_class($e)."] ".$e->getMessage(),array(
				"exception" =>$e,
			),array(
				"type" =>"exception_handler",
				"errno" =>E_ERROR,
				"errstr" =>$e->getMessage(),
				"errfile" =>$e->getFile(),
				"errline" =>$e->getLine(),
				"code" =>$e->getCode(),
				"exception" =>$e,
			));

		} catch (ReportError $e_report) {

			$e_report->shutdown();
		}
	}

	/**
	 * [decorate_value description] 
	 * @param  [type] $target_value [description]
	 * @param  [type] $html_mode [description]
	 * @param  int $level [description]
	 * @return [type]      [description]
	 */
	// 値のHTML出力整形
	public static function decorateValue ($target_value, $html_mode=false, $level=1) {
		
		$result ="";
		$br_code =$html_mode ? "<br/>" : "\n";
		$sp_code =$html_mode ? "&nbsp;" : " ";
		
		if ($level > 20) {
			
			$result ="Report depth ".$level." too deep.";
		
		} elseif ($info =VarsProfiler::profile_function($target_value)) {

			$result .='function: '.$info["name"].'@'.$info["file_short"].'[L'.$info["line"].']'.$br_code;

		} elseif (is_array($target_value)) {
		
			$result .='array('.count($target_value).'):'.$br_code;
			
			if (count($target_value)) {
			
				foreach ($target_value as $key => $value) {
				
					$result .=str_repeat($sp_code,$level*3).'['.$key.']=>'
							.$sp_code.Report::decorateValue($value,$html_mode,$level+1)
							.$br_code;
				}
			}
			
		} elseif (is_object($target_value)) {
		
			$result .='object '.get_class($target_value).
					'('.count($target_value).'):'.$br_code;
			
			$object_vars =method_exists($target_value,"__report")
					? $target_value->__report()
					: get_object_vars($target_value);
					
			if ($object_vars) {
			
				foreach ($object_vars as $key => $value) {
				
					$result .=str_repeat($sp_code,$level*3).'['.$key.']=>'
							.$sp_code.Report::decorateValue($value,$html_mode,$level+1)
							.$br_code;
				}
			}
			
		} else {
			
			if ($target_value === null) {
				
				$result .="null";
			
			} elseif (is_string($target_value)) {
				
				$count =strlen($target_value);
				
				$target_value =$html_mode
						? htmlspecialchars($target_value)
						: str_replace("\n",'\n',$target_value);
				
				$result .="string(".$count."): ".(string)$target_value;
			
			} elseif (is_bool($target_value)) {
				
				$result .="boolean: ".($target_value ? "true" : "false");
				
			} else {
			
				$result .=gettype($target_value).": ".(string)$target_value;
			}
		}
		
		return preg_replace('!'.$br_code.'$!i','',$result);
	}

	/**
	 * [report_template description] 
	 * @param  [type] $errstr [description]
	 * @param  [type] $params [description]
	 * @param  [type] $options [description]
	 * @param  [type] $backtraces [description]
	 * @param  [type] $config [description]
	 * @return [type]      [description]
	 */
	public static function reportTemplate (
			$errstr, 
			$params=null,
			$options=array(),
			$backtraces=array(),
			$config=array()) {

		$libpath =realpath(dirname(__FILE__)."/..");
		$report_filepath =realpath(__FILE__);
	
		$errdetail =array();
		$errset =false;
		$errfile ="";
		$errline ="";
		$errpos ="";
			
		if ($options["errfile"]) {

			$errset =true;
			$errfile ='';
			$errfile .=strstr($options['errfile'],$libpath)!==false ? "rlib/" : "";
			$errfile .=basename($options['errfile']);
			$errline =$options["errline"];
		}
		
		// backtraceの選択
		for ($i=0; $i < count($backtraces); $i++) {
		
			$backtrace =$backtraces[$i];
			$backtrace['file'] =realpath($backtrace['file']);
			
			if ($backtrace['file'] == $report_filepath) {
				
				continue;
			}
			
			$errdetail[$i] ='';
			$errdetail[$i] .=strstr($backtrace['file'],$libpath)!==false ? "rlib/" : "";
			$errdetail[$i] .=basename($backtrace['file']);
			$errdetail[$i] .=($backtrace['line'] ? "(L".$backtrace['line'].") " : "");
			$errdetail[$i] .=' - ';
			$errdetail[$i] .=$backtrace['class'] ? $backtrace['class'].$backtrace['type'] : "";
			$errdetail[$i] .=$backtrace['function'] ? $backtrace['function'] : "";
			
			if ( ! $errset) {
				
				$errfile ='';
				$errfile .=strstr($backtrace['file'],$libpath)!==false ? "rlib/" : "";
				$errfile .=basename($backtrace['file']);
				$errline =$backtrace['line'];
				$errpos ="";
				$errpos .=$backtrace['class'] ? $backtrace['class'].$backtrace['type'] : "";
				$errpos .=$backtrace['function'] ? $backtrace['function'] : "";
			}

			if (strlen($backtrace['line']) && strstr($backtrace['file'],$libpath)===false) {
				
				$errset =true;
			}
		}

		$elm_id ="ELM".sprintf('%07d',mt_rand(1,9999999));
		
		// レポートの整形と出力
		$report_html ="";
		
		// HTML形式
		if ($config["output_format"]=="html") {
			
			$font_color ="#00ff00";
			$elm_class ="";
			$message ="";
			
			if ($options["errno"] & E_USER_NOTICE) { 
			
				$font_color ="#00ff00";
				$elm_class ="notice";
				
			} elseif ($options["errno"] & (E_USER_ERROR | E_ERROR)) { 
			
				$font_color ="#ff0000";
				$elm_class ="warning";
				
			} else {
			
				$font_color ="#ffff00";
				$elm_class ="error";
			}
			
			if (is_array($params) && is_string($errstr)) {
			
				$message .=$errstr;
				
			} else {
			
				$message .=Report::decorateValue($errstr,true);
			}
			
			if (is_array($params)) {
			
				$message .=' :'.Report::decorateValue($params,true);
			}
			
			if (Vars::registry("Report.report_backtraces")) {
			
				$message .='<br/> [BACKTRACES] :'.Report::decorateValue($backtraces,true);
			}
			
			$report_html .='<div class="ruiReport '.$elm_class.'" id="'.$elm_id.'" '
					.'onclick="var e=document.getElementById(\''.$elm_id.'\');'
					.'e.style.height =\'auto\'; e.style.cursor =\'auto\';" '
					.'ondblclick="var e=document.getElementById(\''.$elm_id.'_detail\');'
					.'e.style.display =\'block\'; e.style.cursor =\'auto\';" '
					.'style="font-size:14px;text-align:left;overflow:hidden;'
					.'margin:1px;padding:2px;font-family:monospace;'
					.'border:#888888 1px solid;background-color:'
					.'#000000;cursor:hand;height:40px;color:'.$font_color.'">'
					.$errfile.($errline ? '(L'.$errline.')' : "").' - '.$errpos
					.'<div style="margin:0 0 0 10px">'
					.$message.'</div>'
					.'<div style="margin:0 0 0 10px;display:none;" id="'.$elm_id.'_detail">'
					.'Backtrace: '.Report::decorateValue(array_reverse($errdetail),true).'</div></div>';
		
		// 非HTML形式
		} elseif ( ! $html_mode) {
		
			if ($options["errno"] & E_USER_NOTICE) { 
			
				$report_html .="[REPORT] ";
				
			} elseif ($options["errno"] & (E_USER_ERROR | E_ERROR)) { 
			
				$report_html .="[ERROR] ";
				
			} else {
			
				$report_html .="[WARNING] ";
			}
			
			$report_html .=$errfile.'(L'.$errline.') - '.$errpos."\n";
			
			if (is_string($errstr)) {
			
				$report_html .=$errstr;
				
			} else {
			
				$report_html .=Report::decorateValue($errstr,false);
			}
			
			if (is_array($params)) {
			
				$report_html .=' : '.Report::decorateValue($params,false);
			}
			
			if (is_array($errdetail)) {
			
				$report_html .=' : Backtrace='.Report::decorateValue(array_reverse($errdetail),false);
			}
			
			$report_html .="\n\n";
		}
		
		return $report_html;
	}

	/**
	 * [report description] 
	 * @param  [type] $errstr [description]
	 * @param  [type] $params [description]
	 * @param  [type] $options [description]
	 */
	// レポートドライバ
	public static function report (
			$errstr, 
			$params=null,
			$options=array()) {

		$options["errno"] =$options["errno"]
				? $options["errno"]
				: E_USER_NOTICE;
			
		$backtraces =$options["backtraces"]
				? $options["backtraces"]
				: debug_backtrace();
		
		// レポート出力判定
		if (Webapp::getWebappDync("report") 
				&& (Vars::registry("Report.error_reporting") & $options["errno"])) {

			$config =array();
			$config["output_format"] = ! Webapp::getCliMode() && ! Vars::registry("Report.output_to_file") ? "html" : "plain";
			$html =Report::reportTemplate($errstr,$params,$options,$backtraces,$config);
			
			// ファイル出力
			if ($file_name =Vars::registry("Report.output_to_file")) {
				
				file_put_contents($file_name,$html,FILE_APPEND|LOCK_EX);
				chmod($file_name,0777);

			// Report.buffer_enableによる出力抑止
			} else if ($buffer_level =Vars::registry("Report.buffer_enable")) {

				$report_buffer =& Vars::refGlobals("report_buffer");
				$report_buffer[$buffer_level] .=$html;
				
			// 直接出力
			} else {
			
				print $html;
			}
		}
		
		// エラー時の処理停止
		if ($options["errno"] & (E_USER_ERROR | E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)) {
			
			throw new ReportError(array(
				"errstr" =>$errstr,
				"options" =>$options,
				"params" =>$params,
				"backtraces" =>$backtraces,
			));
		}
	}

	/**
	 * [report_warning description] 
	 * @param  [type] $errstr [description]
	 * @param  [type] $params [description]
	 * @param  [type] $options [description]
	 */
	public static function reportWarning ($message, $params=array(), $options=array()) { 
	
		$options["errno"] =E_USER_WARNING;
		Report::report($message,$params,$options);
	}
	
	/**
	 * [report_error description] 
	 * @param  [type] $message [description]
	 * @param  [type] $params [description]
	 * @param  [type] $options [description]
	 */
	public static function reportError ($message, $params=array(), $options=array()) { 
		
		$options["errno"] =E_USER_ERROR;
		Report::report($message,$params,$options);
	}
	
	/**
	 * [report_buffer_start description] 
	 */
	public static function reportBufferStart () {
		
		$buffer_level =Vars::registry("Report.buffer_enable");
		Vars::registry("Report.buffer_enable",$buffer_level+1);
	}
	
	/**
	 * [report_buffer_end description] 
	 * @param  [type] $all [description]
	 */
	public static function reportBufferEnd ($all=false) { 

		$buffer_level =Vars::registry("Report.buffer_enable");

		// 開始していなければ処理を行わない
		if ( ! $buffer_level) {

			return;
		}
		
		$report_buffer =& Vars::refGlobals("report_buffer");
		$output =$report_buffer[$buffer_level];
		unset($report_buffer[$buffer_level]);

		Vars::registry("Report.buffer_enable",--$buffer_level);

		if ($buffer_level > 0) {

			$report_buffer[$buffer_level] .=$output;

		} else {

			print $output;
		}

		// 全件終了
		if ($all) {

			Report::reportBufferEnd($all);
		}
	}
}