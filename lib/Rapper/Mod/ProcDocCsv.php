<?php

namespace R\Lib\Rapper\Mod;

/**
 * CSV形式ドキュメントの処理
 */
class ProcDocCsv extends BaseMod {

	/**
	 * 
	 */ 
	public function install () {
		
		$r =$this->r;
		
		// ダウンロード処理
		$r->add_filter("proc.src.deploy",array("cond"=>array("data_type"=>"doc_csv")),function($r, $deploy) {
			
			$dest_file =$deploy["dest_file"];
			
			$deploy_data =$deploy["data_callback"]($r);
			$rows =$deploy_data["rows"];
			$data =$deploy_data["data"];
			
			$csv =new CSVHandler("php://memory", "w", array(
				"rows" =>$rows,
			));
			$csv->write_lines($data);
			$fp =$csv->get_file_handle();
			rewind($fp);
			$csv_str =stream_get_contents($fp);
			fclose($fp);
			
			$deploy["src"] =$csv_str;
			
			return $deploy;
		});
		
		// プレビュー処理
		$r->add_filter("proc.preview.deploy",array("cond"=>array("data_type"=>"doc_csv")),function($r, $deploy) {
			
			$deploy_data =$deploy["data_callback"]($r);
			$rows =$deploy_data["rows"];
			$data =$deploy_data["data"];
			
			$html ='<table style="border:black 1px solid;">';
			$html .='<tr style="background-locor:glay;">';
			$html .='<td>[line]</td>';
			
			foreach ($rows as $k=>$v) {
				
				$html .='<td>['.$v.']</td>';
			}
			
			$html .='</tr>';
			
			foreach ($data as $n => $line) {
				
				$html .='<tr>';
				$html .='<td>['.sprintf('%04d',$n+1).']</td>';
				
				foreach ($rows as $k=>$v) {
					
					$html .='<td>'.$line[$k].'</td>';
				}
				
				$html .='</tr>';
			}
			
			$html .='</table>';
			
			$deploy["preview"] =$html;
			
			return $deploy;
		});
	}
}