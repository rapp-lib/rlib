<?php


	//-------------------------------------
	//
	function registry ($name, $value=null) {
		
		$reg =& ref_globals("registry");
		
		// Resq2との互換性（移行過渡期の暫定措置）
		if (is_string($name)
				&& preg_match('!^([^=]+)=(.*)$!',$name,$match)) {
			
			$name =$match[1];
			
			if ($value === null && strlen($match[2])) {
				
				$value =$match[2];
			}
		}
		
		return array_registry($reg, $name, $value);
	}
	
	//-------------------------------------
	//
	function & ref_globals ($name) {
		
		$name ="__".strtoupper($name)."__";
		return $GLOBALS[$name];
	}
	
	//-------------------------------------
	//
	function & ref_session ($name) {
		
		$name ="__".strtoupper($name)."__";
		return $_SESSION[$name];
	}