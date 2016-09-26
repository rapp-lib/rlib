<?php

	function smarty_modifier_autolink ($string, $attrs='target="_blank"') {
		
		$url_ptn ='!(https?|ftp)(://[[:alnum:]\+\$\;\?\!\.%,#~*/:@&=_-]+)!';
		
		$mail_ptn ='!(?:(?:(?:(?:[a-zA-Z0-9_\!#\$\%&\'*+/=?\^`{}~|\-]+)(?:\.(?:[a-zA-Z0-9_\!#\$\%&\'*+/=?\^`{}~|\-]+))*)|(?:"(?:\\[^\r\n]|[^\\"])*")))\@(?:(?:(?:(?:[a-zA-Z0-9_\!#\$\%&\'*+/=?\^`{}~|\-]+)(?:\.(?:[a-zA-Z0-9_\!#\$\%&\'*+/=?\^`{}~|\-]+))*)|(?:\[(?:\\\S|[\x21-\x5a\x5e-\x7e])*\])))!';
		
		$string =preg_replace(
				$url_ptn, 
				'<a href="$0" '.$attrs.'>$0</a>',
				$string);
				
		$string =preg_replace(
				$mail_ptn, 
				'<a href="mailto:$0" '.$attrs.'>$0</a>',
				$string);

		return $string;
	}
	