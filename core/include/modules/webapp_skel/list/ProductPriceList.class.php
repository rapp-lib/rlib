<!?php

//-------------------------------------
// List: <?=$tc["list"]?> 
class <?=str_camelize($tc["list"])?>List extends List_App {
	
	//-------------------------------------
	// オプション取得
	public function options () {
	
		return array(
			"1" =>"No.1",
			"2" =>"No.2",
			"3" =>"No.3",
		);
	}
}