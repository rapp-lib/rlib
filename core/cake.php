<?php
	
	//-------------------------------------
	// CakePHPライブラリの読み込み
	function load_cake ($cake_dir=null) {
		
		// 既にCakeライブラリを読み込んでいるため、スキップ
		if (defined('CAKE_PHP')) {
		
			return;
		
		// 指定されたCakeライブラリを使用
		} elseif ($cake_dir) {
		
			define('ROOT', $cake_dir);
			
			define('APP_DIR', 'app');
			define('DS', DIRECTORY_SEPARATOR);
			define('WEBROOT_DIR', 'webroot');
			define('WWW_ROOT', ROOT . DS . APP_DIR . DS . WEBROOT_DIR . DS);
			define('CAKE_CORE_INCLUDE_PATH', ROOT);
			define('APP_PATH', ROOT . DS . APP_DIR . DS);
			define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
			if ( ! defined('PHP5')) { define('PHP5', (PHP_VERSION >= 5)); }
			if ( ! defined('E_DEPRECATED')) { define('E_DEPRECATED', 8192); }
			error_reporting(E_ALL & ~E_DEPRECATED & ~ E_NOTICE);
			require_once CORE_PATH . 'cake' . DS . 'basics.php';
			$TIME_START = getMicrotime();
			require_once CORE_PATH . 'cake' . DS . 'config' . DS . 'paths.php';
			require_once LIBS . 'object.php';
			require_once LIBS . 'inflector.php';
			require_once LIBS . 'configure.php';
			require_once LIBS . 'set.php';
			require_once LIBS . 'cache.php';
			Configure::getInstance();
			restore_error_handler();
			require_once CAKE . 'dispatcher.php';
			
			define("_LIBS",dirname(__FILE__)."/cake/");
			require_once(_LIBS.'/model/connection_manager.php');
			require_once(_LIBS.'/model/datasources/dbo_source.php');
			
			require_once(LIBS.'/model/model.php');
			
			define("CAKE_PHP",$cake_dir);
			
		// Datasource機能のみ抽出した最小版Cakeの使用
		} else {
		
			define("DS","/");
			define("LIBS",dirname(__FILE__)."/cake/");
			define("CONFIGS",dirname(__FILE__).'/cake/_config/');
			
			class App { 
				static function import() {} 
				function core () {} 
			}
			
			class Configure { 
				public static function read() {} 
			}
			
			class Cache { 
				function read($key, $dist="_default_") {} 
				function write($key, $value, $dist="_default_") {} 
			}
			
			if (!function_exists('pluginSplit')) {
				function pluginSplit($name, $dotAppend = false, $plugin = null) {
					if (strpos($name, '.') !== false) {
						$parts = explode('.', $name, 2);
						if ($dotAppend) {
							$parts[0] .= '.';
						}
						return $parts;
					}
					return array($plugin, $name);
				}
			}
			
			if (!function_exists('getMicrotime')) {
			
				function getMicrotime() {
				
					list($usec, $sec) = explode(' ', microtime());
					return ((float)$usec + (float)$sec);
				}
			}
		
			function __($str,$flg) {
				return $str;
			}
			
			require_once(LIBS.'/object.php');
			require_once(LIBS.'/set.php');
			require_once(LIBS.'/string.php');
			require_once(LIBS.'/inflector.php');
			require_once(LIBS.'/model/connection_manager.php');
			require_once(LIBS.'/model/datasources/dbo_source.php');
			
			define("CAKE_PHP","minimam");
		}
	}
	