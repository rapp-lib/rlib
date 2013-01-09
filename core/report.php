<?php

	
	//-------------------------------------
	// 標準レポートハンドラ
	function std_error_handler (
			$errno, 
			$errstr, 
			$errfile=null, 
			$errline=null, 
			$errcontext=null) {
		
		if (error_reporting() == 0 || ! (error_reporting() & $errno)) {
			
			return; 
		}

		report($errstr,array(),array(
			"type" =>"error_handler",
			"errno" =>$errno,
			"errstr" =>$errstr,
			"errfile" =>$errfile,
			"errline" =>$errline,
			"errcontext" =>$errcontext,
		));
	}
	
	//-------------------------------------
	// 標準例外ハンドラ
	function std_exception_handler ($e) {
	
		report($e->getMessage(),array(),array(
			"type" =>"exception_handler",
			"errno" =>E_ERROR,
			"errstr" =>$e->getMessage(),
			"errfile" =>$e->getFile(),
			"errline" =>$e->getLine(),
			"errcontext" =>$e->getCode(),
		));
	}
	
	//-------------------------------------
	// 値のHTML出力整形
	function decorate_value ($target_value, $html_mode=false, $level=1) {
		
		$result ="";
		$br_code =$html_mode ? "<br/>" : "\n";
		$sp_code =$html_mode ? "&nbsp;" : " ";
		
		if ($level > 20) {
			
			$result ="Report depth ".$level." too deep.";
		
		} elseif (is_array($target_value)) {
		
			$result .='array('.count($target_value).'):'.$br_code;
			
			if (count($target_value)) {
			
				foreach ($target_value as $key => $value) {
				
					$result .=str_repeat($sp_code,$level*3).'['.$key.']=>'
							.$sp_code.decorate_value($value,$html_mode,$level+1)
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
							.$sp_code.decorate_value($value,$html_mode,$level+1)
							.$br_code;
				}
			}
			
		} else {
			
			if ($target_value === null) {
				
				$result .="null";
			
			} elseif (is_string($target_value)) {
				
				$count =strlen($target_value);
				
				$target_value =htmlspecialchars($target_value);
				
				$result .="string(".$count."): ".(string)$target_value;
			
			} elseif (is_bool($target_value)) {
				
				$result .="boolean: ".($target_value ? "true" : "false");
				
			} else {
			
				$result .=gettype($target_value).": ".(string)$target_value;
			}
		}
		
		return preg_replace('!'.$br_code.'$!i','',$result);
	}
	
	function report_template (
			$errstr, 
			$params=null,
			$options=array()) {
			
		if ($options["errfile"] !== null) {
		
			$options["errstr"] .=' ['.basename($options["errfile"]);
		
			if ($options["errline"]) {
			
				$options["errstr"] .='('.$options["errline"].')';
			}
		
			$options["errstr"] .=']';
		}
	
		$libpath =realpath(dirname(__FILE__));
		$backtraces =debug_backtrace();
	
		$errdetail ="\n";
		$errfile ="-";
		$errline ="-";
		$errpos ="";
		
		// backtraceの選択
		for ($i=0; $i < count($backtraces); $i++) {
		
			$backtrace =$backtraces[$i];
			$backtrace['file'] =realpath($backtrace['file']);
			
			if ($i != count($backtraces)-1
					&& ( ! strlen($backtrace['file']) 
					|| strstr($backtrace['file'],$libpath))) {
					
				$errdetail .="@".basename($backtrace['file'])
						."(".$backtrace['line'].") ";
			
				if (strlen($backtrace['class'])) {
			
					$errdetail .=$backtrace['class']."::".$backtrace['function'];
			
				} elseif (strlen($backtrace['function'])) {
			
					$errdetail .=$backtrace['function'];
				
				}
				
				$errdetail .="\n";
				
				continue;
			}
		
			$errfile =basename($backtrace['file']);
			$errline =$backtrace['line'];
			
			if (strlen($backtrace['class'])) {
		
				$errpos =$backtrace['class']."::".$backtrace['function'];
		
			} elseif (strlen($backtrace['function'])) {
		
				$errpos =$backtrace['function'];
			}
		
			break;
		}
		
		$elm_id ="ELM".sprintf('%07d',rand(1,9999999));
		
		$html_mode =ini_get("html_errors") && ! registry("Report.output_to_file");
		
		// レポートの整形と出力
		$report_html ="";
		
		// HTML形式
		if ($html_mode) {
			
			$report_html .='<div id="'.$elm_id.'" '
					.'onclick="var e=document.getElementById(\''.$elm_id.'\');'
					.'e.style.height =\'auto\'; e.style.cursor =\'auto\';" '
					.'style="font-size:14px;text-align:left;overflow:hidden;'
					.'margin:1px;padding:2px;font-family:monospace;'
					.'border:#888888 1px solid;background-color:'
					.'#000000;cursor:hand;height:40px;color:';
			
			if ($options["errno"] & E_USER_NOTICE) { 
			
				$report_html .="#00ff00";
				
			} elseif ($options["errno"] & (E_USER_ERROR | E_ERROR)) { 
			
				$report_html .="#ff0000";
				
			} else {
			
				$report_html .="#ffff00";
			}
			
			$report_html .='"><div style="display:none">'
					.$errdetail.'</div>'
					.$errfile.'('.$errline.') - '.$errpos
					.'<div style="margin:0 0 0 10px">';
		
			if (is_array($params) && is_string($errstr)) {
			
				$report_html .=$errstr;
				
			} else {
			
				$report_html .=decorate_value($errstr,true);
			}
			
			if (is_array($params)) {
			
				$report_html .=' :'.decorate_value($params,true);
			}
			
			$report_html .='</div></div>';
		
		// 非HTML形式
		} elseif ( ! $html_mode) {
		
			if ($options["errno"] & E_USER_NOTICE) { 
			
				$report_html .="[REPORT] ";
				
			} elseif ($options["errno"] & (E_USER_ERROR | E_ERROR)) { 
			
				$report_html .="[ERROR] ";
				
			} else {
			
				$report_html .="[WARNING] ";
			}
			
			$report_html .=$errfile.'('.$errline.') - '.$errpos."\n";
			
			if (is_string($errstr)) {
			
				$report_html .=$errstr;
				
			} else {
			
				$report_html .=decorate_value($errstr,false);
			}
			
			if (is_array($params)) {
			
				$report_html .=' :'.decorate_value($params,false);
			}
			
			$report_html .="\n\n";
		}
		
		return $report_html;
	}
	
	//-------------------------------------
	// レポートドライバ
	function report (
			$errstr, 
			$params=null,
			$options=array()) {
		
		$options["errno"] =$options["errno"]
				? $options["errno"]
				: E_USER_NOTICE;
		
		// Resq2との互換性（移行過渡期の暫定措置）
		if ($params === E_USER_NOTICE
				|| $params === E_USER_WARNING
				|| $params === E_USER_ERROR) {
			
			$options["errno"] =$params;
			$params =null;
			$errstr ="(Legacy) ".$errstr;
		}
		
		// レポート出力判定
		if ((get_webapp_dync("report") || registry("Report.force_reporting"))
				&& (registry("Report.error_reporting") & $options["errno"])) {
			
			$html =report_template($errstr,$params,$options);
			
			$report_buffer =& ref_globals("report_buffer");
			
			// Report.buffer_enableによる出力抑止
			if (registry("Report.buffer_enable")) {
				
				$report_buffer .=$html;
				
			} else {
				
				$html =$report_buffer.$html;
				$report_buffer ="";
				
				// ファイル出力
				if ($file_name =registry("Report.output_to_file") 
						&& is_file($file_name)
						&& is_writable($file_name)) {
					
					file_put_contents($file_name,$html,FILE_APPEND|LOCK_EX);
					
				// 直接出力
				} else {
				
					print $html;
				}
			}
		}
		
		// エラー時の処理停止
		if ($options["errno"] & (E_ERROR | E_USER_ERROR)) {
			
			shutdown_webapp("error_report");
		}
		
		return true;
	}
	
	//-------------------------------------
	//
	function report_warning ($message, $params=array(), $options=array()) { 
	
		$options["errno"] =E_USER_WARNING;
		report($message,$params,$options);
	}
	
	//-------------------------------------
	//
	function report_error ($message, $params=array(), $options=array()) { 
		
		$options["errno"] =E_USER_ERROR;
		report($message,$params,$options);
	}
	
	//-------------------------------------
	//
	function raise_exception ($message) { 
		
		$e =new Exception_App;
		$e->message($message);
		
		throw $e;
	}

//-------------------------------------
//
class Exception_App extends Exception {
	
	protected $message;

	//-------------------------------------
	//
	public function message ($message=null) {
		
		if ($message !== null) {
		
			$this->message =$message;
		}
		
		return $this->message;
	}
}