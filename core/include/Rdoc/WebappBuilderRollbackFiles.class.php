<?php

//-------------------------------------
// 
class WebappBuilderRollbackFiles extends WebappBuilder {
	
	//-------------------------------------
	// ロールバックの実行
	public function rollback_files () {
		
		report("HistoryKey: ".$this->history);
		
		$this->append_history(
				"memo",
				date("Y/m/d H:i"),
				$_SERVER["REQUEST_URI"]."?".$_SERVER["QUERY_STRING"]);
	
		$history =$this->options["history"];
		$action =$this->options["action"];
		$history_file =$this->tmp_dir."/history/".$history;
		
		// 履歴ファイルの確認
		if ( ! file_exists($history_file)) {
			
			report_warning("History file is-not found.",array(
				"history_file" =>$history_file,
			));
			return false;
		}
		
		$lines =file($history_file);
		$st =array();
		
		for ($i<0; $i<count($lines); $i+=3) {
			
			$mode =trim($lines[$i]);
			$src =trim($lines[$i+1]);
			$dest =trim($lines[$i+2]);
			
			if ($action[$mode]) {
				
				$this->rollback_file($mode, $src, $dest);
			}
		}
	}
	
	//-------------------------------------
	// 対象のファイル作成をロールバック
	protected function rollback_file ($mode, $src, $dest) {
		
		// 既存ファイルの移動
		if ($mode == "create") {
			
			if ( ! file_exists($dest)) {
			
				report_warning("Rollback create-file is-not exists.",array(
					"delete_file" =>$dest,
				));
			
				return false;
			}
				
			if ( ! $this->backup_file($dest)) {
				
				return false;
			}
			
			report("Rollback create-file.",array(
				"delete_file" =>$dest,
			));
			
			return true;
			
		// バックアップファイルの復元
		} elseif ($mode == "backup") {
			
			if ( ! file_exists($dest)) {
			
				report_warning("Rollback backup-file failur.",array(
					"backup_file" =>$dest,
					"src_file" =>$src,
				));
			
				return false;
			}
			
			if (file_exists($src)) {
				
				if ( ! $this->backup_file($src)) {
					
					return false;
				}
			}
			
			return $this->deploy_src($src,file_get_contents($dest));
		}
				
		return false;
	}
}