<?php
		
/*
	
	○パラメータについて
			
		[_REQUEST] _UFM[ALIAS]
			group    // ファイルディレクトリ解決に使用
			target   // _FILESへのアクセス... $_FILES[$target];
			         // 省略時は ALIAS_file
			shutdown // trueならばアップ完了後に結果出力して終了
			var_name // 上書きする_REQUESTの変数（ドット参照可)
		
		[Registry] App.UserFileManager.upload_dir
			default ... group空白時のアップロード先
			groups
				<_UFMs.n.group> ... group別アップロード先
		
		[Registry] App.UserFileManager.allow_ext
			default ... group空白時の許可拡張子リスト（"."含む）
			groups
				<_UFMs.n.group> ... group別許可拡張子リスト
		
		[Output] _REQUEST.shutdown時の出力
			<UserFileManager DELETED -> ... 削除完了
			<UserFileManager ERROR ${errcode}> ... エラー
			<UserFileManager UPLOADED ${code}> ... アップロード成功
			
			
	○Sample1（<input type="file">での実現方法）
		
		<input type="hidden" name="DATANAME" value="CODE"/>
		<input type="hidden" name="_UFM[ALIAS][group]" value="GROUP"/>
		<input type="hidden" name="_UFM[ALIAS][var_name]" value="DATANAME"/>
		<input type="file" name="ALIAS_file"/>
		<input type="checkbox" name="_UFM[ALIAS][delete]" value="1"/>
		
		
	○Sample2（jquery.upload.jsでの実現方法）
		
		<input type="hidden" name="DATANAME" value="CODE" id="ALIAS_code"/>
		<a id="ALIAS_trigger">Upload</a>
		<script>
		$(function(){
			new AjaxUpload("ALIAS_trigger", {
				action: "UPLOAD_SCRIPT_URL?"
						+"_UFM[ALIAS][group]=GROUP&"
						+"_UFM[ALIAS][shutdown]=1&",
				data : {},
				onSubmit : function(file , ext){},
				onComplete : function(file, response) {
					if (response.match(/<UserFileManager (\S+) ([^>]+)>/)) {
						var result =RegExp.$1;
						var value =RegExp.$2;
						if (result == "UPLOADED") {
							$("#ALIAS_code").attr("value",value);
						}
					}
				}
			});
		});
		</script>
		
		
	○Sample3（可変数アップロード）
		
		<div style="display:inline;" id="ALIAS_inc_elm">
		</div>
		<div style="display:none;" id="ALIAS_tmpl_elm">
			<div class="ALIAS_item_elm">
				<input type="hidden" name="DATANAME" value="<CODE>"/>
				<input type="hidden" name="_UFM[ALIAS_<INDEX>][group]" 
						value="GROUP"/>
				<input type="hidden" name="_UFM[ALIAS_<INDEX>][var_name]" 
						value="DATANAME.<INDEX>"/>
				<input type="file" name="ALIAS_<INDEX>_file"/>
				<a onclick="window.ALIAS_onclick_remove(this)">[Remove]</a>
				<a onclick="window.ALIAS_onclick_append(this)">[Append]</a>
			</div>
		</div>
		<script>
			window.ALIAS_init ={
				INDEX1: CODE1,
				INDEX2: CODE2
			};
		</script>
		<!-- ここから定型の処理 -->
		<script>
		$(function(){
			window.ALIAS_last =0;
			window.ALIAS_tmpl =$("#ALIAS_tmpl_elm").html();
			window.ALIAS_create_cmd =function (id,code) {
				if (id == undefined) {
					id =window.ALIAS_last+1;
				}
				if (code == undefined) {
					code ="";
				}
				if (id > window.ALIAS_last) {
					window.ALIAS_last =id;
				}
				var tmpl_inst =window.ALIAS_tmpl;
				tmpl_inst =tmpl_inst.replace("<INDEX>",id);
				tmpl_inst =tmpl_inst.replace("<CODE>",code);
				return $(tmpl_inst);
			};
			window.ALIAS_onclick_append =function (trigger_elm) {
				var html =window.ALIAS_create_cmd();
				$(trigger_elm).parent(".ALIAS_item_elm").after(html);
			};
			window.ALIAS_onclick_remove =function (trigger_elm) {
				$(trigger_elm).parent(".ALIAS_item_elm").remove();
			};
			if (window.ALIAS_init != undefined) {
				for (var index in window.ALIAS_init) {
					var html =window.ALIAS_create_cmd(index,window.ALIAS_init[index]);
					$("#ALIAS_inc_elm").append(html);
				}
			}
		});
		</script>
	
	
	○Sample4（<input type="file">にてJSでの削除方法）
		
		<input type="hidden" name="DATANAME" value="CODE" id="ALIAS_code"/>
		<input type="hidden" name="_UFM[ALIAS][group]" value="GROUP"/>
		<input type="hidden" name="_UFM[ALIAS][var_name]" value="DATANAME"/>
		<input type="file" name="ALIAS_file"/>
		<a onclick="$('#ALIAS_code').attr('value','')">[Delete]</a>
		
		
	○HTML上でのサンプルパラメータについて
		
		GROUP ... アップロード先の振り分けに使用します（省略可能）
		ALIAS ... HTML上で一意な名前
		DATANAME ... _REQUESTに使用する名前（配列またはドット参照可能）
		CODE ... アップロード済みファイルの値
		UPLOAD_SCRIPT_URL ... Ajaxアップロードの受け取りURL
*/

//-------------------------------------
// 
class UserFileManager {
	
	protected static $uploaded =null;
	
	//-------------------------------------
	// 
	public function fetch_file_upload_request () {
		
		if (self::$uploaded !== null) {
			
			return self::$uploaded;
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
			
			$upload_dirs =registry("UserFileManager.upload_dir");
			$upload_dir =($group && $upload_dirs["group"][$group])
					? $upload_dirs["group"][$group]
					: $upload_dirs["default"];
			
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
			
			// 拡張子の確認
			if ($allow_ext) {
				
				$ext =preg_match('!\.[a-zA-Z0-9]!',$resource["name"],$match)
						? $match[0]
						: "";
				
				if ( ! in_array($ext,$allow_ext)) {
								
					if ($request["shutdown"]) {
					
						clean_output_shutdown("<UserFileManager ERROR ext_error>");
					}
					
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
			
			$key =($group ? $group : "default")
					."-".date("ymdHis")
					."-".sprintf('%09d',mt_rand(1,mt_getrandmax()));
			$code =$key.$ext;
			$dest_filename =$upload_dir.$key.$ext;
			
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
			
			$result =is_uploaded_file($resource["tmp_name"])
					&& move_uploaded_file($resource["tmp_name"],$dest_filename);
			
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
	// 
	public function get_filename ($code, $group=null) {
		
		$group =preg_replace('![^-_0-9a-zA-Z]!','_',$group);
				
		$upload_dirs =registry("UserFileManager.upload_dir");
		$upload_dir =($group && $upload_dirs["group"][$group])
				? $upload_dirs["group"][$group]
				: $upload_dirs["default"];
		$filename =$upload_dir.$code;
		
		return $filename && file_exists($filename) && ! is_dir($filename)
				? $filename
				: null;
	}
}
	
