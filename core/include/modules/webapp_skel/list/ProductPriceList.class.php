<!?php

//-------------------------------------
// List: <?=$tc["list"]?> 
class <?=str_camelize($tc["list"])?>List extends List_App {
	
	//-------------------------------------
	// オプション取得
	public function options () {
	
		return array(
			"1" =>"Sample1",
			"2" =>"Sample2",
			"3" =>"Sample3",
		);
	}
}