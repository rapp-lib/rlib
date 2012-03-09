<?php

//-------------------------------------
// 
class WebappBuilder {
	
	protected $tmp_dir ="";
	protected $history ="";
	protected $options =array();
	
	//-------------------------------------
	// 
	public function webapp_build () {
		
		$options =registry("Config.dync.webapp_build");
		
		// ロールバックの実行
		// ?exec=1
		// &_[report]=1
		// &_[webapp_build][rollback]=1
		// &_[webapp_build][history]=U1103281310999 ... HistoryKey
		// &_[webapp_build][action][create]=1 ... 作成ファイルの削除
		// &_[webapp_build][action][backup]=1 ... バックアップの復元
		// &_[webapp_build][force]=1 ... 上書きの許可
		if ($options["rollback"]) {
			
			require_once("WebappBuilder/WebappBuilderRollbackFiles.class.php");
			$obj =obj("WebappBuilderRollbackFiles");
			$obj->init($options);
			$obj->rollback_files();
		
		// CSVファイルからSchemaを生成する
		// ?exec=1
		// &_[report]=1
		// &_[webapp_build][schema]=1
		// &_[webapp_build][force]=1 ... 上書きの許可
		} elseif ($options["schema"]) {
			
			require_once("WebappBuilder/WebappBuilderCreateSchema.class.php");
			$obj =obj("WebappBuilderCreateSchema");
			$obj->init($options);
			$obj->create_schema();
			
		// Schemaからソースコードを生成する
		// ?exec=1
		// &_[report]=1
		// &_[webapp_build][deploy]=1
		// &_[webapp_build][force]=1 ... 上書きの許可
		} elseif ($options["deploy"]) {
			
			require_once("WebappBuilder/WebappBuilderDeployFiles.class.php");
			$obj =obj("WebappBuilderDeployFiles");
			$obj->init($options);
			$obj->deploy_files();
			
		// スクリプト構造を解析する
		// ?exec=1
		// &_[report]=1
		// &_[webapp_build][profile]=1
		// &_[webapp_build][target]=lib ... 指定しなければwebapp_dir
		// &_[webapp_build][catalog]=class ... class:クラス一覧 func:関数一覧
		} elseif ($options["profile"]) {
			
			require_once("WebappBuilder/WebappBuilderScriptScanner.class.php");
			$obj =obj("WebappBuilderScriptScanner");
			$obj->init($options);
			$obj->profile_system();
			
		// Readmeを表示する
		// ?exec=1
		// &_[report]=1
		// &_[webapp_build][readme]=1
		// &_[webapp_build][page]=about_lib ... 指定しなければabout_lib
		} elseif ($options["readme"]) {
			
			require_once("WebappBuilder/WebappBuilderReadme.class.php");
			$obj =obj("WebappBuilderReadme");
			$obj->init($options);
			$obj->echo_readme();
			
		// DBの状態を書き換える
		// ?exec=1
		// &_[report]=1
		// &_[webapp_build][datastate]=1
		// &_[webapp_build][dsname]=default ... 指定しなければdefault
		// &_[webapp_build][filename]=test01 ... 使用するファイル名
		// &_[webapp_build][create_dump]=1 ... ダンプ作成
		// &_[webapp_build][restore_dump]=1 ... ダンプ読み込み
		// &_[webapp_build][restore_ds]=1 ... CSV読み込み
		} elseif ($options["datastate"]) {
			
			require_once("WebappBuilder/WebappBuilderDataState.class.php");
			$obj =obj("WebappBuilderDataState");
			$obj->init($options);
			$obj->fetch_datastate();
		}
	}
	
	//-------------------------------------
	// モジュール初期化
	public function init ($options=array()) {
		
		$this->options =$options;
		$this->tmp_dir =$tmp_dir =registry("Path.tmp_dir")."/webapp_build/";
		$this->history ="U".date("ymdHis").sprintf("%03d",rand(001,999));
	}	
	
	//-------------------------------------
	// ファイルの作成
	protected function touch_kindly ($filename) {
		
		if ( ! file_exists(dirname($filename))) {
			
			$old_umask =umask(0);
			mkdir(dirname($filename),0775,true);
			umask($old_umask);
		}
		
		return @touch($filename) && @chmod($filename,0664);
	}
	
	//-------------------------------------
	// テンプレートファイルの参照
	protected function arch_template (
			$src_file, 
			$dest_file, 
			$assign_vars=array()) {
		
		if ( ! is_readable($src_file)) {
			
			return false;
		}
		
		extract($assign_vars,EXTR_REFS);
		ob_start();
		include($src_file);
		$src =ob_get_clean();
		
		$src =str_replace('<!?','<?',$src);
		
		return $this->deploy_src($dest_file,$src);
	}
	
	//-------------------------------------
	// 履歴ファイルへの追記
	protected function append_history ($mode, $src="", $dest="") {
			
		// 履歴ファイルへの追記
		$history_file =$this->tmp_dir."/history/".$this->history;
		
		if ($this->touch_kindly($history_file)) {
		
			$msg =$mode."\n".$src."\n".$dest."\n";
			file_put_contents($history_file,$msg,FILE_APPEND);
		}
	}
	
	//-------------------------------------
	// ファイルのバックアップ
	protected function backup_file ($dest_file) {
			
		$webapp_dir =registry("Path.webapp_dir");
		$backup_file =preg_replace(
				'!^'.preg_quote($webapp_dir).'!',$this->tmp_dir."/backup/",$dest_file)
				.'-'.date("ymd_His");
		
		if ($this->touch_kindly($backup_file)) {
		
			rename($dest_file,$backup_file);
		}
		
		if (file_exists($backup_file)) {
					
			// 履歴ファイルへの追記
			$this->append_history("backup",$dest_file,$backup_file);
			
		} else {
		
			report_warning("Backup failur.",array(
				"dest_file" =>$dest_file,
				"tmp_dir" =>$this->tmp_dir,
				"backup_file" =>$backup_file,
				"tmp_dir_is_writable" =>is_writable($this->tmp_dir),
				"backup_dir_is_writable" =>is_writable(dirname($backup_file)),
			));
			
			return false;
		}
		
		return true;
	}
	
	//-------------------------------------
	// ファイルの書き込み
	protected function deploy_src ($dest_file, $src) {
		
		// 自動展開機能がOFFであれば設定によらず勝手にファイルの上書きを行わない
		if ( ! registry("Config.auto_deploy")) {
			
			$replace_pattern ='!^'.preg_quote(registry("Path.webapp_dir"),'!').'!';
			
			if ( ! preg_match($replace_pattern,$dest_file)) {
				
				return false;
			}
			
			$dest_file =preg_replace(
					$replace_pattern,
					$this->tmp_dir."/deploy/".$this->history."/",
					$dest_file);
		}
		
		// 既存ファイルのバックアップ
		if (file_exists($dest_file) && $this->options["force"]) {
							
			// 同一性チェック
			if ($src !== null) {
			
				$src_dest =file_get_contents($dest_file);
				
				if (crc32($src_dest) == crc32($src)) {
					
					report("File not-changed.",array(
						"dest_file" =>$dest_file,
					));
					
					return true;
				}
			}
		 	
			// バックアップ
			if ( ! $this->backup_file($dest_file)) {
			
				return false;
			}
		}
		
		// 既存ファイルのチェック
		if (file_exists($dest_file)) {
			
			report_warning("Dest File exists",array(
				"dst_file" =>$dest_file,
				"same" =>md5(file_get_contents($dest_file)) == md5($src),
			));
			
			return false;
		}
		
		// ファイルの書き込み
		if (($r_touch =$this->touch_kindly($dest_file))
				&& ($r_writable =is_writable($dest_file))
				&& ($r_write =file_put_contents($dest_file,$src))) {
			
			report("Write-in file successfuly.",array(
				"dest_file" =>$dest_file,
			));
			print "<pre>".htmlspecialchars($src)."</pre>";
		
			// 履歴ファイルへの追記
			$this->append_history("create","",$dest_file);
		
		} else {
			
			report_warning("Fail to write-into file.",array(
				"dest_file" =>$dest_file,
				"r_touch" =>$r_touch,
				"r_writable" =>$r_writable,
				"r_write" =>$r_write,
			));
			return false;
		}
		
		return true;
	}
}