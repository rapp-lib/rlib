<?php

//-------------------------------------
// 
class EcbProvider_Tpro_Basic extends EcbProvider_App {

	//-------------------------------------
	// 初期化
	protected function init ($root_provider=null) {
		
		$this->add_provider(array(
			"item"     =>"ecb_provider.tpro.basic.item",
			//"category" =>"ecb_provider.tpro.basic.category",
			//"order"    =>"ecb_provider.tpro.basic.order",
			//"cart"     =>"ecb_provider.tpro.basic.cart",
			//"member"   =>"ecb_provider.tpro.basic.member",
			"test"     =>"ecb_provider.tpro.basic.test",
		));
	}
	
	public function make_api ($ecb) {
		
		$api =new EcbTproApiProvider_Basic;
		return $api;
	}
}

//-------------------------------------
// 
class EcbTproApiProvider_Basic extends EcbTproApiProvider_App {

	//-------------------------------------
	// 初期化
	protected function init ($root_provider=null) {
		
		$this->add_provider(array(
			//"goods1"   =>"ecb_tpro_api_provider.basic.goods",
		));
	}
}

//-------------------------------------
// 
class EcbProvider_Tpro_Basic_Test extends EcbProvider_App {

	public function call_test_all ($ecb, $params=array()) {
		
		//$t_item =$this->call("item.get_by_id",array("item_id" =>"BA590"));
		//report("item.get_by_id(item_id='BA590')", $t_item);
		
		$t =1;
		$s =microtime(true);
		for ($i=0; $i<$t; $i++) {
			$ts_item =$this->call("item.get_all");
		}
		$e =(microtime(true)-$s)/$t;
		report("time@item.get_all=".round($e*1000)."ms");
		//  time@item.get_all=252ms
		report("item.get_all", $ts_item);
	}
}

//-------------------------------------
// 
class EcbProvider_Tpro_Basic_Item extends EcbProvider_App {
	
	//-------------------------------------
	// 
	public function call_item_get_by_id ($ecb, $params=array()) {
		
        $res =$this->obj("api")->request("/Goods/Service1.asmx/getGoods", array(
			"souko" =>"",
            "gcode" =>$params["item_id"],
        ));
        return $t_item;
	}
	
	//-------------------------------------
	// 
	public function call_item_get_all ($ecb, $params=array()) {
		
        $res =$this->obj("api")->request("/Goods/Service1.asmx/getGoodsAll", array(
			"souko" =>"",
        ));
		$ts_item =$res["diffgr:diffgram"]["Goods"][0]["Goods"];
        return $ts_item;
	}
}
