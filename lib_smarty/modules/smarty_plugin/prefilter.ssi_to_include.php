<?php

	function smarty_prefilter_ssi_to_include ($source, &$smarty){

		//<!--#include virtual="ドキュメントルートからのパス">
		$source = preg_replace( 
			'/\<\!\-\-\#include virtual\=\"\/([0-9a-zA-Z\.\/\_\-]*)\"\-\-\>/i',
			$smarty->left_delimiter . 'include file=\'' . $_SERVER['DOCUMENT_ROOT'] . '/' . "$1" . '\'' . $smarty->right_delimiter,
			$source
		);
		
		//<!--#include file="相対パス" -->
		$source = preg_replace(
			'/\<\!\-\-\#include file\=\"([0-9a-zA-Z\.\/\_\-]*)\"\-\-\>/i',
			$smarty->left_delimiter . 'include file=\'' . "$1" . '\'' . $smarty->right_delimiter,
			$source
		);
		return $source;
	}