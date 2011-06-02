<?php

//-------------------------------------
// 配列を任意フォーマットへ変換
class ArrayConverter {

	//-------------------------------------
	// 配列をXML文字列に変換する
	// ※PearライブラリXML/Serializerを使用
	public function array_to_xml (array $entry,$options=array()) {
		
		require_once('XML/Serializer.php');
		
		$serializer =new XML_Serializer(array(
			"indent"    => "\t",
			"linebreak" => "\n",
			"typeHints" => false,
			"addDecl"   => true,
			"encoding"  => "UTF-8",
			"rootName"  => "root",
			"rootAttributes" => array(),
			"defaultTagName" => "entry",
			"attributesArray" => "@"
		)+$options);
		
		$serializer->Serialize($entry);
		
		return $serializer->getSerializedData(); 
	}

	//-------------------------------------
	// 配列をJSON文字列に変換する
	public function array_to_json ($entry) {
		
		$json ="";
		
		if (is_array($entry)) {
		
			$inner_item =array();
			
			foreach ($entry as $k => $v) {
				
				$inner_item[] =$k.":".$this->array_to_json($v);
			}
			
			$json .="{".implode(",\n",$inner_item)."}";
		
		} else {
			
			$json ="'".str_replace("'","\\'",(string)$entry)."'";
		}
		
		return $json;
	}
}