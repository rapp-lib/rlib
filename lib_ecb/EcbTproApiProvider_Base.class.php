<?php

class EcbTproApiProvider_Base extends ModuleProvider {
	
	protected $config =array();
	
	//-------------------------------------
	// 設定
	public function set_config ($config) {
		
		$this->config =$config;
	}
	
	//-------------------------------------
	// APIの呼び出し
	public function request ($url, $post) {
		
		$url =$this->config["tpro_api_url"].$url;
		$post["id"] =$this->config["tpro_id"];
		
		$http_response =obj("HTTPRequestHandler")->request($url,array(
			"post" =>$post,
		));
		
		// API呼出エラー
		if ($http_response["code"]=="500") {
			
			throw new EcbError("Tpro API Call Error : ".$http_response["body"],array(
				"url" =>$url,
				"post" =>$post,
				"http_response" =>$http_response,
			));
		
		// HTTP通信エラー
		} elseif ($http_response["error"]) {
			
			throw new EcbError("Tpro API HTTP Error",array(
				"url" =>$url,
				"post" =>$post,
				"http_response" =>$http_response,
			));
		}
		
		// BodyXMLを配列に変換
		$res =$this->parse_resopnse_xml($http_response["body"]);
		
		// XML形式エラー
		if ( ! $res) {
			
			throw new EcbError("Tpro API Response XML Error",array(
				"url" =>$url,
				"post" =>$post,
				"response_body" =>$http_response["body"],
			));
		
		// API側のバグ対策、エラー処理不要
		} elseif ($res['message2'] == 'ORA-01008: バインドされていない変数があります。') {
			
		// エラーメッセージ設定
		} elseif (strlen($res['message1']) || strlen($res['message2'])) {
			
			throw new EcbError("Tpro API Error Message : ".$res["message1"]." ".$res["message2"],array(
				"url" =>$url,
				"post" =>$post,
				"message1" =>$res["message1"],
				"message2" =>$res["message2"],
			));
		}
		
		return $res;
	}
	
	//-------------------------------------
	// 配列をAPIリクエスト用XMLに変換する
	public function build_request_xml ($array, $root_elm) {
		
		require_once("XML/Serializer.php");
		
		$serializer =new XML_Serializer(array(
			"indent"          => "\t",
			"linebreak"       => "\n",
			"typeHints"       => false,
			"addDecl"         => true,
			"encoding"        => "UTF-8",
			"rootName"        => $root_elm,
			"rootAttributes"  => array(),
			"attributesArray" => "@",
			"mode"            => "simplexml",
			"xmlDeclEnabled"  => true,
		));
		
		return $serializer->serialize($array)===true
				? $serializer->getSerializedData()
				: null;
	}
	
	//-------------------------------------
	// APIレスポンスXMLを配列に変換する
	public function parse_resopnse_xml ($xml) {
		
		require_once('XML/Unserializer.php');
		
		$unserializer =new XML_Unserializer(array(
			'parseAttributes' => true, 
			'attributesArray' => '@',
			'forceEnum' =>array(
				'Pcode', 'Staff', 'Okuri', 'Hktime', 'Baitai',
				'Baifile', 'Card', 'CardPay', 'MemCallFcode', 'Free1',
				'Free2', 'Free3', 'getHaisoAdrs', 'Goods', 'Rereki',
				'getRirekiDetail', 'WebJyumei', 'Jyumei', 'Hanmei', 'Coupon',
				'Reminder', 'Holidays',
			),
		));
			
		return $unserializer->unserialize($xml) === true
				? $unserializer->getUnserializedData()
				: null;
	}
}