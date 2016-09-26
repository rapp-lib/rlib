<?php 
	
	//-------------------------------------
	// 文書生成
	function rdoc_entry_create_doc ($options=array()) {
		
		// 生成文書の種類
		$doc_type =$options["doc_type"];
		
		// 出力モード
		$output_mode =$options["output_mode"];
		
		$module =load_module("rdoc_create_doc",$doc_type);
		
		if ( ! is_callable($module)) {
			
			report_error("Load rdoc_create_doc-module failur",array(
				"doc_type" =>$doc_type,
			));
		}
		
		list($map,$data) =call_user_func($module);
		
		// プレビューのみ
		if ($output_mode == "preview") {
			
			print get_preview_doc_html($map,$data);
		
		// ダウンロード
        } else if ($output_mode == "download") {
		
			$dest_file =registry("Path.tmp_dir")
					."/doc_output/".$doc_type."-".date("Ymd-His")."-"
					.sprintf("%04d",rand(0,9999)).".csv";
			$csv =new CSVHandler($dest_file, "w", array(
				"file_charset"=>"SJIS-WIN",
				"map" =>$map,
			));
			$csv->write_lines($csv_data);
			
			clean_output_shutdown(array(
				"download" =>basename($dest_file),
				"file" =>$dest_file,
			));
            
        // 配置
        } elseif ($output_mode == "deploy") {

        	$dest_file =registry("Path.webapp_dir")."/config/_".$doc_type.".doc.csv";
        	
        	$s =new RdocSession;
        	$s->deploy_src($dest_file, function($dest_file) use ($map, $data) {
        		
        		$csv =new CSVHandler($dest_file, "w", array(
        			"file_charset"=>"SJIS-WIN",
        			"map" =>$map,
        		));
        		$csv->write_lines($csv_data);
        	});
        }
	}
		
	//-------------------------------------
	// 生成文書のプレビューHTML作成
	function get_preview_doc_html ($map, $data) {
		
		$html ='<table style="border:black 1px solid;">';
		$html .='<tr style="background-locor:glay;">';
		$html .='<td>[line]</td>';
		
		foreach ($map as $v) {
			$html .='<td>['.$v.']</td>';
		}
		
		$html .='</tr>';
		
		foreach ($data as $n => $line) {
			
			$html .='<tr>';
			$html .='<td>['.sprintf('%04d',$n+1).']</td>';
			
			foreach ($map as $v) {
				$html .='<td>'.$line[$v].'</td>';
			}
			
			$html .='</tr>';
		}
		
		$html .='</table>';
		
		return $html;
	}