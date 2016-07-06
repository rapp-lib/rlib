<?php

namespace R\Lib\Rapper\Mod;

/**
 * deploy(data_type=php_tmpl)に対する処理
 */
class ProcPhpTmpl extends BaseMod {

	/**
	 * 
	 */ 
	public function install () {
		
		$r =$this->r;
		
		$r->add_filter("proc.preview.deploy",array("cond"=>array("data_type"=>"php_tmpl")),function($r, $deploy) {
			
			// tmpl_vars/tmpl_schema_varsのアサイン
			$tmpl_vars =(array)$deploy["tmpl_vars"];
			
			foreach ((array)$deploy["tmpl_schema"] as $k => $v) {
				
				$tmpl_vars[$k] =$r->schema($v);
			}
			
			// tmpl_fileの検索
			$src =$r->parse_php_tmpl($deploy["tmpl_file"],$tmpl_vars);
			
			$deploy["preview"] ='<code>'.nl2br(htmlspecialchars($src)).'</code>';
			
			return $deploy;
		});
		
		$r->add_filter("proc.src.deploy",array("cond"=>array("data_type"=>"php_tmpl")),function($r, $deploy) {
			
			// tmpl_vars/tmpl_schema_varsのアサイン
			$tmpl_vars =(array)$deploy["tmpl_vars"];
			
			foreach ((array)$deploy["tmpl_schema"] as $k => $v) {
				
				$tmpl_vars[$k] =$r->schema($v);
			}
			
			// tmpl_fileの検索
			$src =$r->parse_php_tmpl($deploy["tmpl_file"],$tmpl_vars);
			
			$deploy["preview"] ='<code>'.$src.'</code>';
			
			return $deploy;
		});
		
	}
}