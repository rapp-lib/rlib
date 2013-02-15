<?php

	//
	// □ DBの日時バックアップのサンプル
	//    毎日深夜定時にcron設定して使用します
	// 
	// crontab:
	// 		0 3 * * * /usr/bin/php -q /path_to_webapp/cmd/nightly_backup.php"
	// 
	// test-case:
	// 		php -q ./nightly_backup.php
	//

	include_once(dirname(__FILE__)."/../config/config.php");
	
	__start();
	exit;	
	
	//-------------------------------------
	// start
	function __start () {
		
		set_time_limit(0);
		registry("Report.force_reporting",true);
		
		// 終端処理の登録
		register_shutdown_webapp_function("__end");
		
		// 初期設定の適応
		start_webapp();
		
		$info =registry("DBI.preconnect.default");
		$file_id =0;
		
		// 古いバックアップの削除
		foreach (glob($info["backup_file_basename"]."*") as $filename) {
			
			if (preg_match('!-(\d+)-(\d\d\d\d)(\d\d)(\d\d)\.sql\.gz$!',$filename,$match)) {
			
				if ($file_id <= $match[1]) {
				
					$file_id =$match[1]+1;
				}
				
				$timestamp_a =strtotime($match[2].'-'.$match[3]."-".$match[4]);
				$timestamp_b =strtotime(date("Y-m-d"));
				
				if ($timestamp_a+$info["backup_lifecycle"]*24*60*60 < $timestamp_b) {
					
					unlink($filename);
					print "\n".'Delete: '.$filename;
					
				} else {
				
					print "\n".'Leave: '.$filename;
				}
			}
		}
		
		$backup_filename =create_file(
				$info["backup_file_basename"].'-'.$file_id."-".date("Ymd").".sql.gz");
		
		// Postgresの場合のバックアップファイル作成
		if ($info["driver"] == "postgres") {
			
			// パスワードは"~/.pgpass"で設定する必要があります
			// 設定は"localhost:5432:jnavi:dev:pass"の形式
			$cmd ="pg_dump -c";
			if ($info["host"]) { $cmd .=" -h ".$info["host"]; }
			if ($info["login"]) { $cmd .=" -U ".$info["login"]; }
			if ($info["database"]) { $cmd .=" -D ".$info["database"]; }
			$cmd .=" | gzip  > ".$backup_filename.' 2>&1';
			
			exec($cmd,$output);
			print "\n".'Exec: '.$cmd.' ... '.implode("\n",$output)."\n";
		}
		
		shutdown_webapp("normal");
	}
	
	//-------------------------------------
	// __end
	function __end ($cause, $options) {
	}