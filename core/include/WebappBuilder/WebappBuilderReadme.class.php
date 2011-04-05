<?php

//-----------------------------------------------
// Readmeを表示
class WebappBuilderReadme extends WebappBuilder {
	
	//-------------------------------------
	// Readmeを表示
	public function echo_readme () {
		
		$page =$this->options["page"]
				? $this->options["page"]
				: "index";
			
		$module =load_module("readme",$page,true);
		
		echo call_user_func($module, $this->options, $this);
		exit;
	}
}