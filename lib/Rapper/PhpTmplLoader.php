<?php

namespace R\Lib\Rapper;

class PhpTmplParser
{
	/**
	 * 
	 */
	public static function parse ($tmpl_file_path,$tmpl_vars) 
	{
		$tmpl_asset_path ="asset/rapper_tmpl/".$tmpl_file_path;
		$tmpl_file =ClassLoader::findAsset('R\\Lib\\Rapper',$tmpl_asset_path);

		// tmpl_fileの検索
		if ( ! $tmpl_file) {
			report_error("テンプレートファイルがありません",array(
				"tmpl_file" =>$tmpl_asset_path,
			));
		}
		
		// tmpl_varsのアサイン
		$r =$this;
		extract($tmpl_vars,EXTR_REFS);
		
		ob_start();
		include($tmpl_file);
		$src =ob_get_clean();
		$src =str_replace('<!?','<?',$src);
		$src =str_replace('<@?','<?',$src);
		
		return $src;
	}
}