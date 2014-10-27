<?php
	
	//-------------------------------------
	// テンプレート内で定義された{{function}}が{{call}}可能であるか判定
	function smarty_modifier_is_function ($name) {
	
		return is_callable("smarty_template_function_".$name);
	}