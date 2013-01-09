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
		
		$html =call_user_func($module, $this->options, $this);
		
		$charset =registry("Config.external_charset");
		echo '<html><head><meta charset="'.$charset.'">'
				.'</head><body>'.$html.'</body></html>';
		exit;
	}
}