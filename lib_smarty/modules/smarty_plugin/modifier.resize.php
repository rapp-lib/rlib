<?php

	function smarty_modifier_resize ($file_url, $format) {
	
		if ( ! $file_url) {
			
			return null;
		}
		
		$cache_file =obj("ResizeImage")->resize_by_request(array(
			"file_url" =>$file_url,
			"format" =>$format,
		));
		
		return $cache_file
				? file_to_url($cache_file)
				: null;
	}
	