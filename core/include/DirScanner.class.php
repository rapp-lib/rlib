<?php

//-------------------------------------
// ディレクトリ情報の取得
class DirScanner {

	//-------------------------------------
	// ディレクトリ内のファイルを再帰的に取得
	public function scandir_recursive ($path, $dir_including=false, $level=30) {
	
		$dirlist =array();
		
		if ($level) {
			
			foreach (@(array)scandir($path) as $filename) {
				
				if ($filename == '.' || $filename == '..') {
					
					continue;
				
				} else {
					
					$realpath =$this->path_normalize($path.'/'.$filename);
					
					if (is_link($realpath)) {
						
						continue;
					
					} else if (is_file($realpath)) {
						
						$dirlist[] = $realpath;
					
					} else if (is_dir($realpath)) {
						
						$sub_dirlist =$this->scandir_recursive($realpath, $dir_including, $level-1);
						
						if ($dir_including) {
							
							$dirlist[] =$realpath;
						}
						
						foreach ($sub_dirlist as $sub_realpath) {
						
							$dirlist[] =$sub_realpath;
						}
					}
				}  
			}
		}
		
		return (array)$dirlist;
	}
	
	//-------------------------------------
	// パス文字列正規化
	private function path_normalize ($path_str) {
		
		$path_str =realpath($path_str);
		$path_str =str_replace('\\',"/",$path_str);
		$path_str =preg_replace('!^[^/]*(/.*?)/?$!','$1',$path_str);
		
		return $path_str;
	}
}
	