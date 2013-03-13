<?php

	function smarty_modifier_date ($string ,$format="Y/m/d" ) {
		
		return longdate_format($string,$format);
	}
	