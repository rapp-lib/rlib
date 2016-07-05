<?php

namespace R\Lib\Core;

/**
 * 
 */
class ClassLoader 
{
	protected static $map =array();

	/**
	 * NSと読み込み先DIRを設定
	 */
	public static function add($ns, $includePath) 
    {
    	if ( ! is_array(self::$map[$ns])) {

    		self::$map[$ns] =array();
    	}

		self::$map[$ns][] = $includePath;
	}

	/**
	 * SPL autoloader stackに登録
	 */
	public static function install() 
    {
		spl_autoload_register(array(get_class(), 'loadClass'));
	}
	
    /**
     * SPL autoloader stackから登録解除
	 */
	public static function uninstall() 
    {
		spl_autoload_unregister(array(get_class(), 'loadClass'));
	}

	/**
	 * SPL autoloaderから呼び出されるIF
	 */
	public static function loadClass($className)
	{

		foreach (self::$map as $_ns => $_includePaths) {

			// NSが適合しない場合はスキップ
			if ($_ns.'\\' !== substr($className, 0, strlen($_ns.'\\'))) {

				continue;
			}

			// NS部分を切り捨てる（PSR-0不適合の仕様）
			$className =substr($className, strlen($_ns.'\\'));
			
			$fileName = '';

			// クラス名からNSを分離
			if (false !== ($lastNsPos = strripos($className, '\\'))) {
				
				$ns = substr($className, 0, $lastNsPos);
				$className = substr($className, $lastNsPos + 1);
				$fileName = str_replace('\\', '/', $ns) . '/';
			}

			$fileName .= str_replace('_', '/', $className).'.php';

			foreach ($_includePaths as $dir) {
				
				if (file_exists($found = $dir . '/' .$fileName)) {

					// 対象のファイルが発見された場合はrequire後探索終了
					require_once($found);
					return;
				}
			}
		}
	}
}