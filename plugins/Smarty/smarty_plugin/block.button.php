<?php

	function smarty_block_button ($params, $content, $template, &$repeat) {
		
		$template->linkage_block("button", $params, $content, $template, $repeat);
	}
	