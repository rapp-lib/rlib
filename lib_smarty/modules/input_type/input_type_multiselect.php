<?php

	function input_type_multiselect ($params, $preset_value, $postset_value, $template) {
	
		return $template->input_type_select_family(
				$params, $preset_value, $postset_value, $template);
	}