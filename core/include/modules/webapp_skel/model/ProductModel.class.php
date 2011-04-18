<!?php

//-------------------------------------
// Model: <?=$t["label"]?> 
class <?=str_camelize($t["name"])?>Model extends Model_App {

	//-------------------------------------
	// Read: <?=$t["label"]?> ID指定で1件取得
	public function get_<?=str_underscore($t["name"])?> ($id) {
	
		// 要素の取得
		$query =array(
			"table" =>"<?=$t["name"]?>",
			"conditions" =>array(
				"<?=$t['pkey']?>" =>$id,
<? if ($t['del_flg']): ?>
				"<?=$t['del_flg']?>" =>"0",
<? endif; ?>
			),
		);
		$t =DBI::load()->select_one($query);
		
		return $t;
	}

	//-------------------------------------
	// Read: <?=$t["label"]?> 一覧取得
	public function get_<?=str_underscore($t["name"])?>_all ($query=array()) {
	
		// 条件を指定して要素を取得
		$query =$this->merge_query($query, array(
			"table" =>"<?=$t["name"]?>",
			"conditions" =>array(
<? if ($t['del_flg']): ?>
				"<?=$t['del_flg']?>" =>"0",
<? endif; ?>
			),
		));
		$ts =DBI::load()->select($query);
		$p =DBI::load()->select_pager($query);
		
		return array($ts,$p);
	}

	//-------------------------------------
	// Read: <?=$t["label"]?> 一覧ページ取得
	public function get_<?=str_underscore($t["name"])?>_list ($query) {
	
		// 条件を指定して要素を取得
		$query =$this->merge_query($query, array(
			"table" =>"<?=$t["name"]?>",
			"conditions" =>array(
<? if ($t['del_flg']): ?>
				"<?=$t['del_flg']?>" =>"0",
<? endif; ?>
			),
		));
		$ts =DBI::load()->select($query);
		$p =DBI::load()->select_pager($query);
		
		return array($ts,$p);
	}

	//-------------------------------------
	// Write: <?=$t["label"]?> 保存
	public function save_<?=str_underscore($t["name"])?> ($fields, $id=null) {
		
		// IDの指定があれば更新
		if ($id) {
			
			$query =$this->merge_query($query, array(
				"fields" =>$fields,
				"table" =>"<?=$t["name"]?>",
				"conditions" =>array(
					"<?=$t['pkey']?>" =>$id,
<? if ($t['del_flg']): ?>
					"<?=$t['del_flg']?>" =>"0",
<? endif; ?>
				),
			));
			$r =DBI::load()->update($query);
			
			return $r;
		
		// IDの指定がなければ新規登録
		} else {
			
			$query =$this->merge_query($query, array(
				"fields" =>$fields,
				"table" =>"<?=$t["name"]?>",
			));
			$r =DBI::load()->insert($query);
			
			return $r;
		}
	}

	//-------------------------------------
	// Write: <?=$t["label"]?> 削除
	public function delete_<?=str_underscore($t["name"])?> ($id) {
	
<? if ($t['del_flg']): ?>
		// 要素の削除フラグをon
		$query =array(
			"table" =>"<?=$t['name']?>",
			"fields" =>array(
				"<?=$t['del_flg']?>" =>"1",
			),
			"conditions" =>array(
				"<?=$t['pkey']?>" =>$id,
				"<?=$t['del_flg']?>" =>"0",
			),
		);
		$r =DBI::load()->update($query);
<? else: ?>
		// 要素の削除
		$query =array(
			"table" =>"<?=$t["name"]?>",
			"conditions" =>array(
				"<?=$t["name"]?>.id" =>$id,
			),
		);
		$r =DBI::load()->delete($query);
<? endif; ?>
		
		return $r;
	}
}
