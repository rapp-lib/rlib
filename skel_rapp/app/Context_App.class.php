<?php

//-------------------------------------
// Context基本クラス
class Context_App extends Context_Base {

	//-------------------------------------
	// 検索結果ページへのリンクパラメータ組み立て
	public function merge_input ($params) 
	{
		$input =$this->input();
		$input =array_merge($input,$params);
		$this->filter_empty_value($input);
		return $input;
	}

	//-------------------------------------
	// 空白要素の削除
	private function filter_empty_value ( & $values) 
	{
		foreach ($values as $k => $v) {
			if (is_array($v)) {
				$v =$this->filter_empty_value($v);
				if ( ! $v) {
					unset($values[$k]);
				}
			} else if (strlen($v)===0) {
				unset($values[$k]);
			}
		}
		return $values;
	}
}
