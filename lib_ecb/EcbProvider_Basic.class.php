<?php

//-------------------------------------
// 
class EcbProvider_Basic extends EcbProvider_App {
	
	protected $routing =array(
		"category" =>"ecb_provider.basic.category",
		"item"     =>"ecb_provider.basic.item",
		"order"    =>"ecb_provider.basic.order",
		"cart"     =>"ecb_provider.basic.cart",
		"member"   =>"ecb_provider.basic.member",
		"api.request" =>"ecb_provider.basic.tpro_api_connection",
	);
	
	public function ecb_connect ($params) {
		
		$this->registry("config",$params["config"]);
	}
	
	public function ecb_config_get_tpro_id ($params) {
		
		return $this->registry("config.tpro_id");
	}
	
	public function ecb_config_get_tpro_api_url ($params) {
		
		return $this->registry("config.tpro_api_url");
	}
	
	public function ecb_test_all ($params) {
		
		//$t_item =$this->call("item.get_by_id",array("item_id" =>5));
		$ts_item =$this->call("item.get_all");
		report($ts_item);
	}
}

//-------------------------------------
// 
class EcbProvider_Basic_Item extends EcbProvider_App {
	
	public function ecb_item_get_by_id ($params) {
		
        $t_item =$this->call("api.request",array("url"=>"/Goods/Service1.asmx/getGoods", "post"=>array(
            "id" =>$this->call("config.get_tpro_id"),
			"souko" =>"",
            "gcode" =>$params["item_id"],
        )));
        return $t_item;
	}
	
	public function ecb_item_get_all ($params) {
		
        $res =$this->call("api.request",array("url"=>"/Goods/Service1.asmx/getGoodsAll", "post"=>array(
            "id" =>$this->call("config.get_tpro_id"),
			"souko" =>"",
        )));
		$ts_item =$res["diffgr:diffgram"]["Goods"][0]["Goods"];
        return $ts_item;
	}
}

//-------------------------------------
// 
class EcbProvider_Basic_TproApiConnection extends EcbProvider_App {
	
	//-------------------------------------
	// APIの呼び出し
	public function ecb_api_request ($params) {
		return $this->request($params["url"],$params["post"]);
	}
	
	private function request ($url, $post=array()) {
		$url =$this->call("config.get_tpro_api_url").$url;
		$http_response =obj("HTTPRequestHandler")->request($url,array(
			"post" =>$post,
		));
		if ($http_response["code"]=="500") {
			
			throw new ECBError("Tpro API Call Error : ".$http_response["body"],array(
				"url" =>$url,
				"post" =>$post,
				"http_response" =>$http_response,
			));
			
		} elseif ($http_response["error"]) {
			
			throw new ECBError("Tpro API HTTP Error",array(
				"url" =>$url,
				"post" =>$post,
				"http_response" =>$http_response,
			));
		}
		
		$res =$this->xml_to_array($http_response["body"]);
		
		if ( ! $res) {
			
			throw new ECBError("Tpro API Response XML Error",array(
				"message1" =>$res["message1"],
				"message2" =>$res["message2"],
			));
		
		} elseif ($res['message2'] == 'ORA-01008: バインドされていない変数があります。') {
			
			// API側のバグ対策、エラー処理不要
			
		} elseif (strlen($res['message1']) || strlen($res['message2'])) {
			
			throw new ECBError("Tpro API Error Message : ".$res["message1"]." ".$res["message2"],array(
				"url" =>$url,
				"post" =>$post,
				"message1" =>$res["message1"],
				"message2" =>$res["message2"],
			));
		}
		
		return $res;
	}
	
	//-------------------------------------
	// 配列をXMLに変換する
	public function array_to_xml ($array, $root_elm="root") {
		
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
	
	public function xml_to_array ($xml) {
		
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