<?php
/*
	-------------------------------------
	□設定
		
		registry("UserFile.group.[group]",array(
			"upload_dir" => "", // アップロード先
			"allow_ext" => "", // 許可拡張子リスト（"."含む）
			"hash_level" => 3, // ハッシュ階層
            "save_raw_filename" => false, // アップされた元のファイル名を残す
		));
			
	-------------------------------------
	□サンプルコード（アップロード以外のファイルの保存）:

		$result =obj("UserFileManager")->save_file(array(
			"is_uploaded_resource" =>false,
			"group" =>$request["group"],
			"src_filename" =>$tmp_name, 
			"src_filename_alias" =>$name, // 拡張子をつけない場合不要
		));
			
	-------------------------------------
	□サンプルコード（データの直接保存）:

		$result =obj("UserFileManager")->save_file(array(
			"is_uploaded_resource" =>false,
			"group" =>$request["group"],
			"src_data" =>$data, 
			"src_filename_alias" =>$name,  // 拡張子をつけない場合不要
		));
		
*/

//-------------------------------------
// 
class UserFileManager {
	
	//-------------------------------------
	// グループ別設定を取得する
	protected function get_config ($name, $group, $must=false) {
			
		$must_code =$must ? "!" : "";
		$group =preg_replace('![^_0-9a-zA-Z]!','_',$group);
		
		/// DEPRECATED 141016 : 古い設定記述方法への対応
		if (registry("UserFileManager")) {
			
			return $group
					? registry($must_code."UserFileManager.".$name.".group.".$group)
					: registry($must_code."UserFileManager.".$name.".default");
		}
		
		return $group
				? registry($must_code."UserFile.group.".$group.".".$name)
				: null;
	}
	
	//-------------------------------------
	// アップロード完了時のローカルファイル名を取得
	public function get_uploaded_file ($code, $group) {
		
		$code =preg_replace('!\.\.!','__',$code);
		
		$upload_dir =$this->get_config("upload_dir",$group,true);
		
		$filename =$upload_dir."/".$code;
		
		// 対象が空
		if ( ! $code) {
			
			return null;
		}
		
		// ファイルがない
		$file_notfound = ! file_exists($filename) || ! is_file($filename);
		
		// 参照時に手元にファイルがなかったらダウンロードを行う指定がある場合
		if ($file_notfound && $this->get_config("fetch_remote_file",$group)) {
			
			$transfar =$this->get_config("transfar",$group);
			
			// SCP転送
			if ($transfar["type"] == "scp") {
				
				$result =obj("SSHTransfar")->scp_download(array(
					"local_file" =>$filename,
					"remote_file" =>$transfar["scp_config"]["remote_dir"]."/".$code,
					"host" =>$transfar["scp_config"]["host"],
					"port" =>$transfar["scp_config"]["port"],
					"user" =>$transfar["scp_config"]["user"],
					"identity_file" =>$transfar["scp_config"]["identity_file"],
				));
				
				// 通信失敗
				if ( ! $result) {
					
					return null;
				}
			}
		
			$file_notfound = ! file_exists($filename) || is_dir($filename);
		}
		
		if ($file_notfound) {
			
			return null;
		}
		
		return $filename;
	}
	
	//-------------------------------------
	// Codeに対応するファイルの配信URLを取得
	public function get_url ($code, $group) {
	
		// URLの解決方法の指定がある場合
		if ($upload_url =$this->get_config("upload_url",$group)) {
			
			return $upload_url."/".$code;
		}
		
		// DocumentRoot以下のファイルで自動的にURL解決できる場合		
		$filename =$this->get_uploaded_file($code, $group);
		$url =file_to_url($filename);
		
		return $url;
	}
	
	//-------------------------------------
	// 新規のアップロードファイルのコードを生成
	public function get_blank_key ($group) {
					
		$key =date("Ymd-His")."-".sprintf('%03d',mt_rand(1,999));
		
		// ファイル名をハッシュディレクトリで階層化する
		$hash_level =$this->get_config("hash_level",$group);
		
		// ハッシュ階層化
		if ($hash_level) {
			
			$hash_table =array_splice(preg_split('!!',md5($key)),1,$hash_level);
			$key =implode("/",$hash_table)."/".$key;
		}
		
		return $key;
	}
	
	//-------------------------------------
	// ファイルを保存
	public function save_file ($params) {
		
		$group =$params["group"];
		
		// アップロードファイルがない
		if (($params["src_filename"] && ! file_exists($params["src_filename"]))
				|| ( ! $params["src_filename"] && ! strlen($params["src_data"]))) {

			return array(
				"status" =>"no_file",
			);
		}

		// 許可する拡張子の指定がある場合、指定以外拒否するが、拡張子は削除しない
		$ext ="";
		
		if ($params["src_filename_alias"]
				&& $allow_ext =$this->get_config("allow_ext",$group)) {
			
			$ext =preg_match('!\.[^\.]+$!',$params["src_filename_alias"],$match)
					? strtolower($match[0])
					: "";
			
			if ( ! in_array(str_replace('.','',$ext),$allow_ext)) {
			
				return array(
					"status" =>"denied",
					"reaseon" =>"ext_denied",
					"ext" =>$ext,
					"message" =>"ext:".$ext." is denied for group:".$group,
				);
			}
		}
		
		// 保存先ディレクトリチェック
		$upload_dir =$this->get_config("upload_dir",$group,true);
		
		$is_dir_writable =$upload_dir 
				&& is_dir($upload_dir)
				&& is_writable($upload_dir);
				
		if ( ! $is_dir_writable) {
			
			return array(
				"status" =>"error",
				"message" =>"upload_dir:".$upload_dir." is-not writable",
			);
		}
		
		// 保存先ファイル名の生成
		$key =$this->get_blank_key($group);
		$code =$key.$ext;
		
		// アップロード時のファイル名を残す設定
		if ($this->get_config("save_raw_filename",$group)
				&& $params["src_filename_alias"]) {
			
			// 拡張子が許可されている場合のみ指定を有効にする
			if ($ext) {
			
				$code =$key."/".basename($params["src_filename_alias"]);
			}
		}
		
		$dest_filename =$upload_dir."/".$code;
		
		// 既存ファイル衝突チェック（通常乱数の衝突が発生することはない）
		if (file_exists($dest_filename)) {
			
			return array(
				"status" =>"error",
				"message" =>"dest_file:".$dest_filename." already exists",
			);
		}
	
		// フォルダ作成
		if ( ! file_exists(dirname($dest_filename))) {
			
			mkdir(dirname($dest_filename),0775,true);
		}
		
		// アップロード
		$result =false;
		
		if ($params["is_uploaded_resource"]) {
		
			$result =is_uploaded_file($params["src_filename"])
					&& move_uploaded_file($params["src_filename"],$dest_filename);
		
		// ファイルのコピー
		} elseif ($params["src_filename"]) {
			
			$result =copy($params["src_filename"],$dest_filename);
		
		// ファイルの直接保存
		} else {
			
			$result =file_put_contents($dest_filename,$params["src_data"]);
		}
		
		// ファイルの書き込みエラー
		if ( ! $result) {
			
			return array(
				"status" =>"error",
				"message" =>"dest_file:".$dest_filename." could-not be-written",
			);
		}
		
		chmod($dest_filename,0664);
		
		// アップロードファイルのリモートへの転送の指定がある場合
		if ($transfar =$this->get_config("transfar",$group)) {
			
			// SCP転送
			if ($transfar["type"] == "scp") {
				
				$result =obj("SSHTransfar")->ssh_remote_exec(array(
					"mkdir",
					"-p",
					dirname($transfar["scp_config"]["remote_dir"]."/".$code),
				),array(
					"host" =>$transfar["scp_config"]["host"],
					"port" =>$transfar["scp_config"]["port"],
					"user" =>$transfar["scp_config"]["user"],
					"identity_file" =>$transfar["scp_config"]["identity_file"],
				));
				
				$result =obj("SSHTransfar")->scp_upload(array(
					"local_file" =>$dest_filename,
					"remote_file" =>$transfar["scp_config"]["remote_dir"]."/".$code,
					"host" =>$transfar["scp_config"]["host"],
					"port" =>$transfar["scp_config"]["port"],
					"user" =>$transfar["scp_config"]["user"],
					"identity_file" =>$transfar["scp_config"]["identity_file"],
				));
				
				// 通信失敗
				if ( ! $result) {
					
					return array(
						"status" =>"transfar_failed",
						"message" =>"scp transfar failed",
					);
				}
			}
		}
		
		return array(
			"status" =>"success",
			"code" =>$code,
			"file" =>$dest_filename,
			"url" =>$this->get_url($code,$group),
		);
	}
	
	//-------------------------------------
	// アップロードディレクトリを取得 
	/// DEPRECATED 141016 get_configに移行
	public function get_upload_dir_DEPRECATED ($group=null) {
		
		$group =preg_replace('![^-_0-9a-zA-Z]!','_',$group);
				
		$upload_dirs =registry("UserFileManager.upload_dir");
		$upload_dir =($group && $upload_dirs["group"][$group])
				? $upload_dirs["group"][$group]
				: $upload_dirs["default"];
		
		return $upload_dir;
	}
	
	//-------------------------------------
	// アップロード完了時のファイル名を取得
	/// DEPRECATED 141016 get_uploaded_file に移行
	public function get_filename ($code, $group) {
		
		return $this->get_uploaded_file($code, $group);
	}
}
	
