<?php 

	function ecb ($name="default") {
		
		$loaded =& ref_globals("loaded_ecb");
	
		if ( ! $loaded[$name]) {
			
			$connection =registry("ECB.connection.".$name);
			$provider_class =$connection["provider_class"];
			$loaded[$name] =new $provider_class;
			$loaded[$name]->call("connect",array("config"=>$connection["config"]));
		}
		
		return $loaded[$name];
	}

// XML解析、通信エラーなどECバックエンド都合のエラー
// →エラーページを表示する
class EcbError extends Exception {
	
	public function __construct ($msg, $data) {
		
		$this->message =$msg;
		report_warning("[EcbError] ".$msg,$data);
	}
}

// 購入導線系（非決済）のエラー
// →状況をカートまで戻す
//class EcbTransactionError extends EcbError {}

// 決済系のエラー
// →可能な限り復旧させる
//class EcbPaymentError extends EcbTransactionError {}