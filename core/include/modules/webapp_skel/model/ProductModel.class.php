<!?php

//-------------------------------------
// Model: <?=$t["label"]?> 
class <?=str_camelize($t["name"])?>Model extends Model_App {

	//-------------------------------------
	// Read: <?=$t["label"]?> ID指定で1件取得
	public function get_by_id ($id) {
	
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
		$t =dbi()->select_one($query);
		
		return $t;
	}

	//-------------------------------------
	// Read: <?=$t["label"]?> 一覧取得
	public function get_all () {
	
		// 条件を指定して要素を取得
		$query =array(
			"table" =>"<?=$t["name"]?>",
			"conditions" =>array(
<? if ($t['del_flg']): ?>
				"<?=$t['del_flg']?>" =>"0",
<? endif; ?>
			),
<? if ($t['reg_date']): ?>
			"order" =>"<?=$t['reg_date']?>",
<? endif; ?>
			
		);
		$ts =dbi()->select($query);
		
		return $ts;
	}

	//-------------------------------------
	// Read: <?=$t["label"]?> 一覧ページ取得
	public function get_list ($list_setting, $input) {
		
		// 条件を指定して要素を取得
		$query =$this->get_list_query($list_setting, $input);
		
		$query =$this->merge_query($query, array(
			"table" =>"<?=$t["name"]?>",
			"conditions" =>array(
<? if ($t['del_flg']): ?>
				"<?=$t['del_flg']?>" =>"0",
<? endif; ?>
			),
		));
		$ts =dbi()->select($query);
		$p =dbi()->select_pager($query);
		
		return array($ts,$p);
	}

	//-------------------------------------
	// Write: <?=$t["label"]?> 保存
	public function save ($fields, $id=null) {
		
		// IDの指定があれば更新
		if ($id) {
			
<? if ($t['update_date']): ?>
			$fields["<?=$t['update_date']?>"] =date('Y/m/d H:i:s');
			
<? endif; ?>
			$query =array(
				"fields" =>$fields,
				"table" =>"<?=$t["name"]?>",
				"conditions" =>array(
					"<?=$t['pkey']?>" =>$id,
<? if ($t['del_flg']): ?>
					"<?=$t['del_flg']?>" =>"0",
<? endif; ?>
				),
			);
			$r =dbi()->update($query);
			
			return $r;
		
		// IDの指定がなければ新規登録
		} else {
			
<? if ($t['reg_date']): ?>
			$fields["<?=$t['reg_date']?>"] =date('Y/m/d H:i:s');
			
<? endif; ?>
<? if ($t['del_flg']): ?>
			$fields["<?=$t['del_flg']?>"] ="0";
			
<? endif; ?>
			$query =array(
				"fields" =>$fields,
				"table" =>"<?=$t["name"]?>",
			);
			$r =dbi()->insert($query);
			
			return $r;
		}
	}

	//-------------------------------------
	// Write: <?=$t["label"]?> 削除
	public function delete ($id) {
	
<? if ($t['del_flg']): ?>
		// 要素の削除フラグをon
		$query =array(
			"table" =>"<?=$t['name']?>",
			"fields" =>array(
				"<?=$t['del_flg']?>" =>"1",
<? if ($t['update_date']): ?>
				"<?=$t['update_date']?>" =>date('Y/m/d H:i:s'),
<? endif; ?>
			),
			"conditions" =>array(
				"<?=$t['pkey']?>" =>$id,
				"<?=$t['del_flg']?>" =>"0",
			),
		);
		$r =dbi()->update($query);
<? else: ?>
		// 要素の削除
		$query =array(
			"table" =>"<?=$t["name"]?>",
			"conditions" =>array(
				"<?=$t["name"]?>.id" =>$id,
			),
		);
		$r =dbi()->delete($query);
<? endif; ?>
		
		return $r;
	}
}
