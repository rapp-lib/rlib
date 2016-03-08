<?php

//-------------------------------------
//
class SchemaCsvLoader {

	//-------------------------------------
	// SchemaCSVファイルを読み込む
	public function load_schema_csv ($filename) {

		$csv =new CSVHandler($filename,"r",array(
			"file_charset" =>"SJIS-WIN",
            "data_charset" =>"UTF-8",
		));

		// コマンド行のデータ
		$mode ="";
		$header_line =array();

		// 親の情報を継承するためのデータ
		$parent_data =array();
        
        // 行の意味を決定するためのデータ
        $line_mode =array();
        $line_id =null;

		// 組み立て結果となるSchema
		$s =array();

		foreach ($csv->read_all() as $current_line) {

			// コメント行→無視
			if ($current_line[0] == "#") { continue; }

			// コマンド列＝#xxx→読み込みモード切り替え
			if (preg_match('!^#(.+)$!',$current_line[0],$match)) {

                $header_line =array();
                $parent_data =array();

                foreach ($current_line as $k => $v) {

                    // コマンド列,空白→無視
                    if ($k==0 || ! strlen(trim($v))) { continue; }

                    // 親子関係の解決
                    if (preg_match('!^#(.+)$!',$v,$match)) {

                        $v =$match[1];
    				    $parent_data[$v] =null;
                    }

                    $header_line[$k] =trim($v);
                }

				continue;
			}

			// モード切替列で意味に関連付け
            $line_mode =null;
            $line_id =null;
			$current_data =array();

			foreach ($current_line as $k => $v) {

				// コマンド列→無視
                if ($k==0 || ! strlen(trim($v))) { continue; }
                
				$current_data[$header_line[$k]] =trim($v);
			}
            
            // 行の意味と親情報の更新
            foreach ($parent_data as $parent_key => $parent_value) {
                
                if ($line_mode) {
                    
                    $parent_data[$parent_key] =$current_data[$parent_key];
                }
                
                if (strlen($current_data[$parent_key])) {
                    
                    $parent_data[$parent_key] =$current_data[$parent_key];
                    $line_mode =$parent_key;
                    $line_id .=$current_data[$parent_key];
                
                } else if ( ! $line_mode) {
                    
                    $line_id .=$parent_value.".";
                }
            }
            
			// 空行
			if ( ! implode($current_data,"")) { continue; }

			// 不正な行
			if ( ! $line_mode || ! $line_id) {
            			
				report_warning("不正なSchema行",array(
                    "mode" =>$mode,
                    "line_mode" =>$line_mode,
                    "line_id" =>$line_id,
					"header_line" =>$header_line,
					"current_data" =>$current_data,
					"parent_data" =>$parent_data,
				));
                
                continue;
            }
            
            // ID登録
            $current_data["_id"] =$line_id;
            
            // 親のデータの登録
            foreach ($parent_data as $k => $v) {
                
                if (strlen($v)) {
                    
                    $current_data[$k] =$v;
                }
            }
                
    		// Schemaデータ参照へのデータ登録
            $line_ref =$line_mode.".".$line_id;
            
			foreach ($current_data as $k => $v) {
                
                // "[a=1|2]"形式の項目は配列として登録
				if (preg_match('!^\s*\[(.*?)\]\s*$!',$v,$match1)) {
                    
            		foreach (preg_split("!(\r?\n)|[\|]!",$match1[1]) as $v_split) {
                        
                        // "a=1"形式であれば連想配列として登録
            			if (preg_match('!^([^=]+)=(.+)$!',$v_split,$match2)) {
                            
                            if (preg_match('!^\.!',$k)) {
                                
                                $ref = & ref_array($s,$line_ref.".".trim($match2[1]));
                                $ref =trim($match2[2]);
                            
                            } else if ($k) {
                                
                                $ref = & ref_array($s,$line_ref.".".$k.".".trim($match2[1]));
                                $ref =trim($match2[2]);
                            }
                        
                        // "b"形式であれば配列として登録
                        } else {
                            
                            if (preg_match('!^\.!',$k)) {
                                
                                $ref = & ref_array($s,$line_ref);
                                $ref[] =trim($v_split);
                                
                            } else if ($k) {
                                
                                $ref = & ref_array($s,$line_ref.".".$k);
                                $ref[] =trim($v_split);
                            }
                        }
                    }
                    
				} else if ($k) {
                    
                    $ref = & ref_array($s,$line_ref.".".$k);
                    $ref =trim($v);
                }
			}
		}
        
        return $s;
	}
}