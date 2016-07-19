<?php

use R\Lib\Core\Vars;
	//date_default_timezone_set('Asia/Tokyo');
 	
	// [Transit] NS対応版のautoloadの読み込み
	require_once(dirname(__FILE__).'/core/include/Transit.class.php');
	Transit::installAutoload();

	Modules::addIncludePath(dirname(__FILE__).'/core/include/');
	Modules::addIncludePath(dirname(__FILE__).'/core/pear/');
	

	// MEMO : core/inculdeの中に、coreディレクトリ内の関数がどこに定義されてるかを一覧としてリフレクション
	function registry ($name, $value=null) {
		return Vars::registry($name, $value=null);
	}