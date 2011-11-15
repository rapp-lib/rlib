<?php

	//-------------------------------------
	// ファイルの作成
	function touch_kindly ($filename, $file_chmod=0664) {
		
		if ( ! file_exists(dirname($filename))) {
			
			mkdir(dirname($filename),0775,true);
		}
		
		return @touch($filename) && @chmod($filename,$file_chmod);
	}