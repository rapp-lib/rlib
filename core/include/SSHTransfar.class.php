<?php

//-------------------------------------
// リモートとのSSH通信
class SSHTransfar {
	
	//-------------------------------------
	// SCPによるダウンロード
	public function scp_download ($options=array()) {
		
		return $this->scp("download",$options);
	}
	
	//-------------------------------------
	// SCPによるアップロード
	public function scp_upload ($options=array()) {
		
		return $this->scp("upload",$options);
	}
	
	//-------------------------------------
	// SCPによる通信
	protected function scp ($mode, $options=array()) {
		
		// 秘密鍵の指定
		if ($options["identity_file"]) {
			
			$options["params"]["-i"] =$options["identity_file"];
		}
		
		// ポートの指定
		if ($options["port"]) {
			
			$options["params"]["-P"] =$options["port"];
		}
		
		$local_file =$this->escape($options["local_file"]);
		$remote_file =$this->escape($options["user"])."@"
				.$this->escape($options["host"]).":"
				.$this->escape($options["remote_file"]);
		
		// コマンド構築
		$cmd ="";
		$cmd .="scp ";
		$cmd .=$this->escape($options["params"]).' ';
		
		if ($mode == "download") {
			
			$cmd .=$remote_file." ".$local_file.' ';
		
		} elseif ($mode == "upload") {
			
			$cmd .=$local_file." ".$remote_file.' ';
			
		} else {
			
			report_error("SCP transfar-mode missing",array(
				"mode" =>$mode,
				"options" =>$options,
			));
			
			return false;
		}
		
		$cmd .='2>&1 ';
			
		// 転送実行
		exec($cmd,$output,$result);
		
		// 完了ステータスの確認
		$success =$result == 0;
		
		$report_param =array(
			"mode" =>$mode,
			"options" =>$options,
			"cmd" =>$cmd,
			"output" =>$output,
			"result" =>$result,
		);
		
		if ($success) {
		
			report("SCP transfar completed",$report_param);
		
		} else {
		
			report_warning("SCP transfar error",$report_param);
		}
		
		return $success;
	}
	
	//-------------------------------------
	// SSHによるリモートコマンドの実行
	public function ssh_remote_exec ($command, $options=array()) {
		
		// ユーザの指定
		if ($options["user"]) {
			
			$options["params"]["-l"] =$options["user"];
		}
		
		// 秘密鍵の指定
		if ($options["identity_file"]) {
			
			$options["params"]["-i"] =$options["identity_file"];
		}
		
		// ポートの指定
		if ($options["port"]) {
			
			$options["params"]["-p"] =$options["port"];
		}
		
		// コマンド構築
		$cmd ="";
		$cmd .="ssh ";
		$cmd .=$this->escape($options["params"]).' ';
		$cmd .=$this->escape($options["host"]).' ';
		$cmd .=$this->escape($this->escape($command)).' ';
		$cmd .='2>&1 ';
		
		// 転送実行
		exec($cmd,$output,$result);
		
		// 完了ステータスの確認
		$success =$result != 255;
		
		$report_param =array(
			"command" =>$command,
			"args" =>$args,
			"options" =>$options,
			"cmd" =>$cmd,
			"output" =>$output,
			"result" =>$result,
		);
		
		if ($success) {
		
			report("SSH exec completed",$report_param);
		
		} else {
		
			report_warning("SSH exec error",$report_param);
		}
		
		return $result;
	}
	
	//-------------------------------------
	// コマンドに渡すパラメータのエスケープ
	public function escape ($value) {
	
		$escaped_value =null;
		
		// 引数配列
		if (is_array($value)) {
			
			$escaped_value =array();
			
			foreach ($value as $k => $v) {
				
				if (is_string($k)) {
					
					$escaped_value[] =$this->escape($k)."".$this->escape($v);
					
				} else {
					
					$escaped_value[] =$this->escape($v);
				}
			}
			
			$escaped_value =implode(" ",$escaped_value);
			
		// 文字列
		} elseif (is_string($value)) {
			
			$escaped_value =escapeshellarg($value);
		}
		
		return $escaped_value;
	}
}
