<?php

/*
サンプルコード
-------------------------------------
	$mt =new MovableTypeAdapter("./cache/");
	$mt->load_blog("sample_01","../sample_01/vk.xml");
	$mt->get_blog_data("sample_01");


Movabletype4カスタムテンプレート（vk.xml）
-------------------------------------
	<$mt:HTTPContentType type="application/atom+xml"$><?xml version="1.0" encoding="<$mt:PublishCharset$>"?>
	<root>
	
	 <value name="blog_info">
	  <value name="title"><$mt:BlogName remove_html="1" encode_xml="1"$></value>
	  <value name="link"><$mt:BlogName remove_html="1" encode_xml="1"$></value>
	  <value name="update"><mt:Entries lastn="1"><$mt:EntryModifiedDate utc="1" format="%Y/%m/%d %H:%M:%S"$></mt:Entries></value>
	 </value>
	 
	<mt:Entries lastn="1000">
	 <value name="entries" index="[]" type="array">
	  <value name="title"><$mt:EntryTitle remove_html="1" encode_xml="1"$></value>
	  <value name="link"><$mt:EntryPermalink encode_xml="1"$></value>
	  <value name="update"><$mt:EntryModifiedDate utc="1" format="%Y/%m/%d %H:%M:%S"$></value>
	  
	  <mt:EntryCategories>
	   <value name="categories" index="[]"><$mt:CategoryLabel encode_xml="1"$></value>
	  </mt:EntryCategories>
	  
	  <mt:EntryIfTagged><mt:EntryTags>
	   <value name="tags" index="[]"><$mt:TagName normalize="1" encode_xml="1"$></value>
	  </mt:EntryTags></mt:EntryIfTagged>
	  
	  <value name="custom_fields" type="array">
	   <mt:EntryCustomFields> 
	    <value name="<mt:CustomFieldName />"><mt:CustomFieldValue /></value>
	   </mt:EntryCustomFields>
	  </value>
	  
	  <value name="content_short"><$mt:EntryBody encode_xml="1"$></value>
	  <value name="content"><$mt:EntryBody encode_xml="1"$><$mt:EntryMore encode_xml="1"$></value>
	 </value>
	</mt:Entries>
	
	</root>
*/

//-------------------------------------
// MovableTypeへの接続
class MovableTypeAdapter {
	
	protected $cache_dir =null;
	protected $blog_data =array();
	
	//-------------------------------------
	// コンストラクタ
	public function __construct ($cache_dir=null) {
		
		$this->cache_dir =$cache_dir;
	}

	//-------------------------------------
	// ブログデータの読み込み
	public function load_blog ($name, $vkxml_file) {
		
		$this->blog_data[$name] =$this->parse_vkxml_file($vkxml_file, $this->cache_dir);
	}

	//-------------------------------------
	// ブログデータの取得
	public function get_blog_data ($name) {
		
		return $this->blog_data[$name];
	}

	//-------------------------------------
	// VKXMLファイルの解析
	protected function parse_vkxml_file ($vkxml_file, $cache_dir=null) {
			
		if ($cache_dir === null) {
			
			$_xml =@simplexml_load_file($vkxml_file);
			return $this->fetch_vkxml_node($_xml);
		}
		
		$vkxml_file_cache =$cache_dir.'/'.md5($vkxml_file).".vk.xml";
		$vkxml_values_cache =$cache_dir.'/'.md5($vkxml_file).".array";
	
		if ($this->is_same_file($vkxml_file,$vkxml_file_cache)) {
			
			return unserialize(file_get_contents($vkxml_values_cache));
		}
		
		$_xml =@simplexml_load_file($vkxml_file);
		$values =$this->fetch_vkxml_node($_xml);
		
		copy($vkxml_file,$vkxml_file_cache);
		file_put_contents($vkxml_values_cache,serialize($values));
		
		return $values;
	}
	
	//-------------------------------------
	// VKXMLノードの再帰的な解析
	protected function fetch_vkxml_node ($_root) {
		
		$values =array();
		
		foreach ($_root->xpath("value") as $_node) {
			
			$name =(string)$_node["name"];
			$type =(string)$_node["type"];
			$value =count($_node->children())
					? (array)$this->fetch_vkxml_node($_node)
					: (string)$_node;
			
			if ($type == "string") {
				
				$value =(string)$value;
				
			} elseif ($type == "int") {
				
				$value =(int)$value;
				
			} elseif ($type == "array" && ! is_array($value)) {
				
				$value =array();
			}
			
			if ( ! $name) {
				
				continue;
				
			} elseif ($_node["index"] == "[]") {
				
				if ( ! is_array($values[$name])) {
					
					$values[$name] =array();
				}
				
				$values[$name][] =$value;
				
			} elseif (is_numeric($_node["index"])) {
			
				$values[$name][(int)$_node["index"]] =$value;
			} else {
			
				$values[$name] =$value;
			}
		}
		
		return $values;
	}

	//-------------------------------------
	// ファイル同一性チェック
	protected function is_same_file ($fn1, $fn2) {
	
		if ( ! is_readable($fn1) || ! is_file($fn1)
				|| ! is_readable($fn2) || ! is_file($fn2)
				|| filetype($fn1) !== filetype($fn2)
				|| filesize($fn1) !== filesize($fn2)) {
		
			return false;
		}
		
		if ( ! $fp1 = fopen($fn1, 'rb')) {
		
			return false;
		}
		
		if ( ! $fp2 = fopen($fn2, 'rb')) {
		
			fclose($fp1);
			return false;
		}
		
		$same = true;
		
		while ( ! feof($fp1) && ! feof($fp2)) {
		
			if(fread($fp1, 4096) !== fread($fp2, 4096)) {
			
				$same = false;
				break;
			}
		}
		
		if (feof($fp1) !== feof($fp2)) {
		
			$same = false;
		}
		
		fclose($fp1);
		fclose($fp2);
		
		return $same;
	}
}