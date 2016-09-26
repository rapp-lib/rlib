<?php
    
    // 呼び出し元別設定（$_SERVER["LOADER_ID"]にSetEnvIf等で設定されたIDで振り分け）
    /*
	registry("Config.loaders", array(
		"default" =>array(
			"overwrite_config" =>array(
        		"Path.html_dir" =>registry("Path.webapp_dir")."/html2",
        		"Path.document_root_dir" =>registry("Path.webapp_dir")."/html2",
			),
        ),
	));
    */