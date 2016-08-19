<?php
/*
	2016/07/17
		core/file.php内の全関数の移行完了

 */
namespace R\Lib\Core;

/**
 * 
 */
class File {

	/**
	 * [touch_kindly description] 
	 * @param  string $filename [description]
	 * @param  int $file_chmod [description]
	 * @return [type]      [description]
	 */
	// ファイルの作成
	public static function touchKindly ($filename, $file_chmod=0664) {
		
		if ( ! file_exists(dirname($filename))) {
			
			mkdir(dirname($filename),0775,true);
		}
		
		return @touch($filename) && @chmod($filename,$file_chmod);
	}
	
	/**
	 * [create_file description] 
	 * @param  string $filename [description]
	 * @param  int $mode [description]
	 * @return [type]      [description]
	 */
	// ファイルを作成する
	function createFile ($filename, $mode=0644) {
		
		if ( ! file_exists(dirname($filename))) {
			
			mkdir(dirname($filename),0755,true);
		}
		
		if (fclose(fopen($filename,"w"))) {
			
			return $filename;
			
		} else {
		
			return null;
		}
	}
}
