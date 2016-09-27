<?php
/*
	2016/07/07
		core/vars.php内の全関数の移行完了

 */
namespace R\Lib\Core;

use R\Lib\Core\Arr;
/**
 * 
 */
class Vars {

	/**
	 * [registry description] 
	 * @param  string $name [description]
	 * @param  string $value [description]
	 * @return [type]      [description]
	 */
	public static function registry ($name, $value=null) {
		
		$reg =& Vars::refGlobals("registry");
		
		// Resq2との互換性（移行過渡期の暫定措置）
		if (is_string($name)
				&& preg_match('!^([^=]+)=(.*)$!',$name,$match)) {
			
			$name =$match[1];
			
			if ($value === null && strlen($match[2])) {
				
				$value =$match[2];
			}
		}
		
		return Arr::registry($reg, $name, $value);
	}
	
	/**
	 * [ref_globals description] 
	 * @param  string $name [description]
	 * @return [type]      [description]
	 */
	public static function & refGlobals ($name) {
		
		$name ="__".strtoupper($name)."__";
		return $GLOBALS[$name];
	}
	
	/**
	 * [ref_session description] 
	 * @param  string $name [description]
	 * @return [type]      [description]
	 */
	public static function & refSession ($name) {
		
		$name ="__".strtoupper($name)."__";
		return $_SESSION[$name];
	}
}
