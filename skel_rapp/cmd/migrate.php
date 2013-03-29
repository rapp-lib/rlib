<?php
/* 
-------------------------------------
データ保全処理
-------------------------------------

■DBのバックアップ：
	php -q migrate.php dbdump 
			--file=/.../base ... バックアップの基本ファイル名
			[--cycle=7] ... 何日以上前のファイルを消すか
			[--ds=default] ... DBIのdatasourceの指定

■Cronの設定（例:毎晩深夜0305）：
	5 3 * * *		/usr/bin/php -q /.../migrate.php...
*/

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
		
		$params =get_cli_params();
		$command =$params[0];
		
		//-------------------------------------
		// DBの深夜Backup
		if ($command=="dbdump") {
			
			$backup_file_base =$params["file"];
			$backup_lifecycle =$params["cycle"]
					? $params["cycle"]
					: 7;
			$ds =$params["ds"] 
					? $params["ds"]
					: "default";
			$db_connection =registry("DBI.connection.".$ds);
			
			// パラメータエラー
			if ( ! $backup_file_base || ! $db_connection) {
				
				report_error("Args error",array(
					"command" =>$command,
					"params" =>$params,
				));
			}
			
			// 古いバックアップの確認／削除
			$file_id =0;
			
			foreach (glob($backup_file_base."*") as $filename) {
				
				if (preg_match('!-(\d+)-(\d\d\d\d)(\d\d)(\d\d)\.sql\.gz$!',$filename,$match)) {
				
					$fileid_a =$match[1];
					$timestamp_a =strtotime($match[2].'-'.$match[3]."-".$match[4]);
					$timestamp_b =strtotime(date("Y-m-d"));
					
					if ($file_id <= $fileid_a) {
					
						$file_id =$fileid_a+1;
					}
					
					if ($timestamp_a+$backup_lifecycle*24*60*60 < $timestamp_b) {
						
						unlink($filename);
						print "\n".'Delete: '.$filename;
						
					} else {
					
						print "\n".'Leave: '.$filename;
					}
				}
			}
			
			$backup_filename =$backup_file_base.'-'.$file_id."-".date("Ymd").".sql.gz";
			
			// Mysql
			if ($db_connection["driver"] == "mysql") {
				
				$cmd ="mysqldump";
				if ($db_connection["host"]) { $cmd .=" -h ".$db_connection["host"]; }
				if ($db_connection["login"]) { $cmd .=" -u ".$db_connection["login"]; }
				if ($db_connection["password"]) { $cmd .=" -p".$db_connection["password"]; }
				if ($db_connection["database"]) { $cmd .=" ".$db_connection["database"]; }
				$cmd .=' | gzip  > "'.$backup_filename.'" 2>&1';
				
				exec($cmd,$output);
				print "\n".'Complete: '.$cmd.' ... '.implode("\n",$output)."\n";
			}
			
			// Postgres
			if ($db_connection["driver"] == "postgres") {
				
				// パスワードは"~/.pgpass"で設定する必要があります
				// 設定は"localhost:5432:jnavi:dev:pass"の形式
				$cmd ="pg_dump -c";
				if ($db_connection["host"]) { $cmd .=" -h ".$db_connection["host"]; }
				if ($db_connection["login"]) { $cmd .=" -U ".$db_connection["login"]; }
				if ($db_connection["database"]) { $cmd .=" -D ".$db_connection["database"]; }
				$cmd .=' | gzip  > "'.$backup_filename.'" 2>&1';
				
				exec($cmd,$output);
				print "\n".'Complete: '.$cmd.' ... '.implode("\n",$output)."\n";
			}
			
		} else {
			print 1;
			report_error("Command error",array(
				"command" =>$command,
				"params" =>$params,
			));
		}
		
		shutdown_webapp("normal");
	}
	
	//-------------------------------------
	// __end
	function __end ($cause, $options) {
	}