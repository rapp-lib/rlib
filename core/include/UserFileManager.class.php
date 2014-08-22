<?php
/*
	-------------------------------------
	□設定
		
		[Registry] App.UserFileManager.upload_dir
			default ... group空白時のアップロード先
			groups
				<_UFMs.n.group> ... group別アップロード先
		
		[Registry] App.UserFileManager.allow_ext
			default ... group空白時の許可拡張子リスト（"."含む）
			groups
				<_UFMs.n.group> ... group別許可拡張子リスト

	-------------------------------------
	□サンプルコード（フォーム以外からのファイルアップロード）:

		$result =obj("UserFileManager")->save_file(array(
			"is_uploaded_resource" =>false,
			"group" =>$request["group"],
			"src_filename" =>$tmp_name, 
			"src_filename_alias" =>$name, 
		));
*/

//-------------------------------------
// 
class UserFileManager {
	
	//-------------------------------------
	// アップロード済みのファイルがあればそのファイル名を取得
	public function get_filename ($code, $group=null) {
			
		$code =preg_replace('!\.\.!','_',$code);
		
		$upload_dir =$this->get_upload_dir($group);
		$filename =$upload_dir."/".$code;
		
		return $filename && file_exists($filename) && ! is_dir($filename)
				? $filename
				: null;
	}
	
	//-------------------------------------
	// アップロードディレクトリを取得
	public function get_upload_dir ($group=null) {
		
		$group =preg_replace('![^-_0-9a-zA-Z]!','_',$group);
				
		$upload_dirs =registry("UserFileManager.upload_dir");
		$upload_dir =($group && $upload_dirs["group"][$group])
				? $upload_dirs["group"][$group]
				: $upload_dirs["default"];
		
		return $upload_dir;
	}
	
	//-------------------------------------
	// 新規のアップロードファイルのコードを生成
	public function get_blank_key ($group=null) {
					
		$key =date("Ymd-His")."-".sprintf('%03d',mt_rand(1,999));
		
		// ファイル名をハッシュディレクトリで階層化する
		$hash_levels =registry("UserFileManager.hash_level");
		$hash_level =($group && $hash_levels["group"][$group])
				? $hash_levels["group"][$group]
				: $hash_levels["default"];
		
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
		
		$upload_dir =$this->get_upload_dir($group);
		
		$allow_exts =registry("UserFileManager.allow_ext");
		$allow_ext =($group && $allow_exts["group"][$group])
				? $allow_exts["group"][$group]
				: $allow_exts["default"];
	
		// アップロードファイルがない
		if ( ! file_exists($params["src_filename"])) {

			return array(
				"status" =>"no_file",
			);
		}

		// 拡張子の確認
		$ext ="";
		
		if ($allow_ext) {
			
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
		
		$is_dir_writable =$upload_dir 
				&& is_dir($upload_dir)
				&& is_writable($upload_dir);
				
		// 保存先ディレクトリチェック
		if ( ! $is_dir_writable) {
			
			return array(
				"status" =>"error",
				"message" =>"upload_dir:".$upload_dir." is-not writable",
			);
		}
		
		$key =$this->get_blank_key($group);
		$code =$key.$ext;
		$dest_filename =$upload_dir."/".$key.$ext;
		
		// 既存ファイル衝突チェック
		if (file_exists($dest_filename)) {
			
			return array(
				"status" =>"error",
				"message" =>"dest_file:".$dest_filename." already exists",
			);
		}
	
		// フォルダ作成
		if ( ! file_exists(dirname($dest_filename))) {
			
			mkdir(dirname($dest_filename),0777,true);
		}
		
		// アップロード
		$result =false;
		
		if ($params["is_uploaded_resource"]) {
		
			$result =is_uploaded_file($params["src_filename"])
					&& move_uploaded_file($params["src_filename"],$dest_filename);
		} else {
			
			$result =copy($params["src_filename"],$dest_filename);
		}
		
		if ( ! $result) {
			
			return array(
				"status" =>"error",
				"message" =>"dest_file:".$dest_filename." could-not be-written",
			);
		}
		
		chmod($dest_filename,0666);

		return array(
			"status" =>"success",
			"code" =>$code,
			"file" =>$dest_filename,
			"url" =>file_to_url($dest_filename),
		);
	}
	
	//-------------------------------------
	// <DEPLECATED 131204 save_fileに移行>
	protected static $uploaded_DEPLECATED =null;
	
	//-------------------------------------
	// <DEPLECATED 131204 save_fileに移行 input_type_file_oldから呼び出しは可能>
	public function fetch_file_upload_request_DEPLECATED () {
		
		if (self::$uploaded !== null) {
			
			return self::$uploaded;
		}
		
		if ( ! isset($_REQUEST["_UFM"])) {
			
			return;
		}
		
		$requests =$_REQUEST["_UFM"];
		
		self::$uploaded =array();
			
		foreach ((array)$requests as $request_index => $request) {
			
			if ( ! is_array($request)) {
				
				$request =array();
			}
			
			$group =preg_replace('![^-_0-9a-zA-Z]!','_',$request["group"]);
			
			$target =$request["target"]
					? $request["target"]
					: $request_index."_file";
			$resource =$_FILES[$target];
			$delete =$request["delete"];
			$complex =$request["complex"];
			
			$var_name =$request["var_name"]
					? $request["var_name"]
					: $request_index;
			$name_ref =$var_name;
			$name_ref =str_replace('.','..',$name_ref);
			$name_ref =str_replace('][','.',$name_ref);
			$name_ref =str_replace('[','.',$name_ref);
			$name_ref =str_replace(']','',$name_ref);
			$overwrite_target_var =& ref_array($_REQUEST,$name_ref);
			
			$upload_dir =$this->get_upload_dir($group);
			
			$allow_exts =registry("UserFileManager.allow_ext");
			$allow_ext =($group && $allow_exts["group"][$group])
					? $allow_exts["group"][$group]
					: $allow_exts["default"];
			
			// 削除判定
			if ($delete) {
				
				self::$uploaded[$request_index] =array(
					"deleted" =>true,
				);
				
				if ($request["shutdown"]) {
				
					clean_output_shutdown("<UserFileManager DELETED ->");
				}
				
				$overwrite_target_var ="";
				
				continue;
			}
			
			// アップロードファイルがない
			if ( ! is_uploaded_file($resource["tmp_name"])) {
			
				report("No file to upload.",array(
					"request_index" =>$request_index,
					"group" =>$group,
					"upload_dir" =>$upload_dir,
					"resource" =>$resource,
					"request" =>$request,
				));
				
				continue;
			}
			
			// 拡張子の確認
			$ext ="";
			
			if ($allow_ext) {
				
				$ext =preg_match('!\.[^\.]+$!',$resource["name"],$match)
						? $match[0]
						: "";
				
				if ( ! in_array(str_replace('.','',strtolower($ext)),$allow_ext)) {
								
					if ($request["shutdown"]) {
					
						clean_output_shutdown("<UserFileManager ERROR ext_error>");
					}
				
					report_warning("File upload error. ext not-allowed.",array(
						"request_index" =>$request_index,
						"group" =>$group,
						"upload_dir" =>$upload_dir,
						"target_file" =>$resource["name"],
						"ext" =>$ext,
					));
					
					self::$uploaded[$request_index] =array(
						"error" =>"ext_error",
					);
					
					continue;
				}
			}
			
			$dir_writable =$upload_dir 
					&& is_dir($upload_dir)
					&& is_writable($upload_dir);
					
			// 保存先ディレクトリチェック
			if ( ! $dir_writable) {
									
				if ($request["shutdown"]) {
				
					clean_output_shutdown("<UserFileManager ERROR internal_error_dir>");
				}
				
				report_warning("File upload error. upload_dir is not writable.",array(
					"request_index" =>$request_index,
					"group" =>$group,
					"upload_dir" =>$upload_dir,
				));
				
				self::$uploaded[$request_index] =array(
					"error" =>"internal_error_dir",
				);
				
				continue;
			}
			
			$key =$this->get_blank_key($group);
			$code =$key.$ext;
			$dest_filename =$upload_dir."/".$key.$ext;
			
			// 既存ファイル衝突チェック
			if (file_exists($dest_filename)) {
				
				if ($request["shutdown"]) {
				
					clean_output_shutdown("<UserFileManager ERROR internal_error_file>");
				}
				
				report_warning("File upload error. File already exists.",array(
					"request_index" =>$request_index,
					"group" =>$group,
					"upload_dir" =>$upload_dir,
					"dest_filename" =>$dest_filename,
				));
				
				self::$uploaded[$request_index] =array(
					"error" =>"internal_error_file",
				);
				
				continue;
			}
		
			// フォルダ作成
			if ( ! file_exists(dirname($dest_filename))) {
				
				mkdir(dirname($dest_filename),0777,true);
			}
			
			$result =move_uploaded_file($resource["tmp_name"],$dest_filename)
					&& chmod($dest_filename,0664);
			
			// アップロード可否確認
			if ( ! $result) {
				
				if ($request["shutdown"]) {
				
					clean_output_shutdown("<UserFileManager ERROR internal_error_upload>");
				}
				
				report_warning("File upload error. Upload failur",array(
					"request_index" =>$request_index,
					"group" =>$group,
					"upload_dir" =>$upload_dir,
					"dest_filename" =>$dest_filename,
					"resource" =>$resource,
				));
				
				self::$uploaded[$request_index] =array(
					"error" =>"internal_error_upload",
				);
				
				continue;
			}
			
			$url =file_to_url($dest_filename);
			
			self::$uploaded[$request_index] =array(
				"upload_dir" =>$upload_dir,
				"key" =>$key,
				"ext" =>$ext,
				"group" =>$group,
				"url" =>$url,
				"filename" =>$dest_filename,
				"code" =>$code,
			);
			
			if ($request["shutdown"]) {
			
				clean_output_shutdown("<UserFileManager UPLOADED ".$code.">");
			}
			
			if ($request["complex"]) {
				
				$overwrite_target_var =$request["complex"];
				$overwrite_target_var["code"] =$code;
				
			} else {
			
				$overwrite_target_var =$code;
			}
		}
		
		return self::$uploaded;
	}
	
	//-------------------------------------
	// 指定したデータをアップロード<DEPLECATED 131204 save_fileに移行>
	public function upload_data_DEPLECATED (
			$data, 
			$code=null, 
			$group=null, 
			$data_is_filename=false) {
		
		$code =$code
				? $code
				: $this->get_blank_key($group);
		$upload_dir =$this->get_upload_dir($group);
		$filename =$upload_dir."/".$code;
		
		// フォルダ作成
		if ( ! file_exists(dirname($filename))) {
			
			mkdir(dirname($filename),0777,true);
		}
		
		$dir_writable =$upload_dir 
				&& is_dir($upload_dir)
				&& is_writable($upload_dir);
		
		// 保存先ディレクトリチェック
		if ( ! $dir_writable) {
		
			report_warning("File upload error. upload_dir is not writable.",array(
				"group" =>$group,
				"upload_dir" =>$upload_dir,
				"filename" =>$filename,
			));
			
			return null;
		}
		
		// 既存ファイル衝突チェック
		if (file_exists($filename)) {
		
			report_warning("File upload error. File already exists.",array(
				"group" =>$group,
				"upload_dir" =>$upload_dir,
				"filename" =>$filename,
			));
			
			return null;
		}
	
		$result =$data_is_filename
				? copy($data,$filename)
				: file_put_contents($filename,$data);
		
		if ($result) {

			chmod($filename,0666);
		}
		
		return $result
				? $code
				: null;
	}
	
	//-------------------------------------
	// 指定したファイルをアップロード<DEPLECATED 131204 save_fileに移行>
	public function upload_file_DEPLECATED ($target_file, $code=null, $group=null) {
		
		if ( ! file_exists($target_file)
				|| ! is_readable($target_file)
				|| is_dir($target_file)) {
		
			report_warning("File upload error. Target file is-not readable.",array(
				"group" =>$group,
				"upload_dir" =>$upload_dir,
				"filename" =>$filename,
			));
			
			return null;
		}
		
		return $this->upload_data($target_file,$code,$group,true);
	}
}
	
