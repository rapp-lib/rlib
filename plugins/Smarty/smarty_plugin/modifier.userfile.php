<?php

	function smarty_modifier_userfile ($code, $group=null) {
		
		return obj("UserFileManager")->get_url($code,$group);
	}
	