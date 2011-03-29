<?php

	function smarty_modifier_userfile ($code, $group=null, $type="url") {
		
		$filename =obj("UserFileManager")->get_filename($code,$group);
		
		if ($type == "url") {
			
			return file_to_url($filename);
		
		} elseif ($type == "file") {
			
			return $filename;
		}
	}
	