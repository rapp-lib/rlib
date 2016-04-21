<?php

	//-------------------------------------
	// page_to_pathのマップを得る
	function & get_page_to_path_map ($flip=false) {

		static $cache;

		if ( ! $cache) {

			foreach ((array)registry("Routing.page_to_path") as $k1 => $v1) {

				foreach ($v1 as $k2 => $v2) {

					$cache["page_to_path"][$k1.".".$k2] =$v2;
				}
			}

			$cache["path_to_page"] =array_flip($cache["page_to_path"]);
		}

		return $cache[$flip ? "path_to_page" : "page_to_path"];
	}

	//-------------------------------------
	// 相対Path指定の解決
	function relative_path ($path) {

		if (preg_match('!^\.(.*)$!',$path,$match)) {

			$path =file_to_url(dirname(registry("Request.request_file"))).$path;
		}

		return $path;
	}

	//-------------------------------------
	// 相対Page指定の解決
	function relative_page ($page) {

		if (preg_match('!^([^\.]+)?(?:\.([^\.]+))?$!',$page,$match)) {

			$page ="";
			$page .=$match[1] ? $match[1] : registry("Request.controller_name");
			$page .=".";
			$page .=$match[2] ? $match[2] : "index";

		} elseif ($page == ".") {

			$page ="";
			$page .=registry("Request.controller_name");
			$page .=".";
			$page .=registry("Request.action_name");
		}

		return $page;
	}

	//-------------------------------------
	// PathからPageを得る（主にRouting時）
	// extract_url_paramsでURL内パラメータも取得
	function path_to_page ($path, $extract_url_params=false) {

		$path =relative_path($path);
		$path_to_page =& get_page_to_path_map(true);
		$page =$path_to_page[$path];
		$params =array();

		// 解決できない場合はパターンマッチ
		if ( ! $page) {

			foreach ($path_to_page as $to_path => $to_page) {

				if (preg_match_all('!\[([^\]]+)\]!',$to_path,$matches)) {

					$param_keys =$matches[1];
					$to_path_ptn ='!'.preg_quote($to_path,'!').'!';
					$to_path_ptn =preg_replace('!\\\\\[.*?\\\\\]!','(.*?)',$to_path_ptn);
					$to_path_ptn =preg_replace('!\(\.\*\?\)\!$!','(.*)!',$to_path_ptn);

					if (preg_match($to_path_ptn,$path,$match)) {

						array_shift($match);

						foreach ($match as $k => $v) {

							$params[$param_keys[$k]] =$v;
						}

						$path =$to_path;
						$page =$to_page;
					}
				}
			}
		}

		return $extract_url_params
				? array($page,$path,$params)
				: $page;
	}

	//-------------------------------------
	// 指定したpathがリストに該当するか
	function in_path ($path, $list) {

		$page =path_to_page($path);
		$result =false;

		foreach ((array)$list as $item) {

			// path指定
			if (preg_match('!^(?:path:)?(/[^\*]*)(\*)?$!',$item,$match)) {

				$result =$match[2]
						? strpos($path,$match[1])===0
						: $path==$match[1];

			// page指定
			} elseif ($page && preg_match('!^(?:page:)?([^\*]+)(\*)?$!',$item,$match)) {

				$result =$match[2]
						? strpos($page,$match[1])===0
						: $page==$match[1];
			}

			if ($result) {

				return $item;
			}
		}

		return false;
	}

	//-------------------------------------
	// PageからPathを得る
	function page_to_path ($page) {

		$page =relative_page($page);
		$page_to_path =& get_page_to_path_map();
		$path =$page_to_path[$page];

		return $path;
	}

	//-------------------------------------
	// Pathからファイル名を得る
	function path_to_file ($path) {

		$path =relative_path($path);
		return registry("Path.html_dir").$path;
	}

	//-------------------------------------
	// ファイル名からURLを得る
	function file_to_url ($file, $full_url=false) {

		$document_root_url =$full_url
				? registry('!Path.document_root_url')
				: "";
		$document_root_url =preg_replace('!/$!','',$document_root_url);

		// https指定であればURLの先頭を変更
		if ($full_url === "https") {
            
			if ($document_root_ssl_url =registry('Path.document_root_ssl_url')) {

				$document_root_url =preg_replace('!/$!','',$document_root_ssl_url);

			} else {

				$document_root_url =preg_replace('!^http://!','https://',$document_root_url);
			}
		
		// httpから始まるURLを返す必要がなければ切り取る
		} elseif ( ! $full_url) {
			
			$document_root_url =preg_replace('!^https?://[^/]+(/|$)!','',$document_root_url);
		}

		$pattern ='!^'.preg_quote(registry('!Path.document_root_dir')).'/?!';
        
        // DocumentRoot外のファイルにURLは存在しない
		if ( ! preg_match($pattern,$file)) {
            
            return null;
        }
        
		$url =preg_replace($pattern,$document_root_url."/",$file);
		$url =apply_url_rewrite_rules($url);
        
		return $url;
	}

	//-------------------------------------
	// PathからURLを得る（主にRedirectやHREFに使用）
	function path_to_url ($page, $full_url=false) {

		$file =path_to_file($page);
		$url =file_to_url($file,$full_url);

		return $url;
	}

	//-------------------------------------
	// URLからPathを得る
	function url_to_path ($url, $index_filename="index.html") {
	
		$document_root_dir =registry("Path.document_root_dir");
		$html_dir =registry("Path.html_dir");
		
		$url =preg_replace('!^https?://[^/]+!','',$url);
		$url =preg_replace('!\#.*$!','',$url);
		$url =preg_replace('!\?.*$!','',$url);
		
		$file =preg_replace('!(/[^\.]*?)/?$!','$1/'.$index_filename,$document_root_dir.$url);
		$path =preg_replace('!^'.preg_quote($html_dir).'!','',$file);
		
		return $path;
	}

	//-------------------------------------
	// Pageからファイル名を得る
	function page_to_file ($page) {

		$page =relative_page($page);
		$path =page_to_path($page);
		$file =$path
				? registry("Path.html_dir").$path
				: null;

		return $file;
	}

	//-------------------------------------
	// PageからURLを得る（主にRedirectやHREFに使用）
	function page_to_url ($page, $full_url=false) {

		$page =relative_page($page);
		$file =page_to_file($page);
		$url =file_to_url($file,$full_url);

		return $url;
	}
		
	//-------------------------------------
	// 対象ファイルがDocumentRoot配下にあるか確認
	function is_public_file ($file) {
		
		$dir =registry("Path.document_root_dir");
		
		return ! preg_match('!/\.\.+/!',$file) 
				&& preg_match('!^'.preg_quote($dir,'!').'!',$file);
	}
