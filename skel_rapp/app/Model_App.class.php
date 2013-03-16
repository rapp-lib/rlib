<?php

//-------------------------------------
// Model基本クラス
class Model_App extends Model_Base {
	
	//-------------------------------------
	// データ取得条件の更新
	public function before_select ( & $query) {
	}
	
	//-------------------------------------
	// INSERTフィールドの更新
	public function before_create ( & $query) {
	}
	
	//-------------------------------------
	// UPDATEフィールドの更新
	public function before_update ( & $query) {
	}
	
	//-------------------------------------
	// DELETE条件の更新
	public function before_delete ( & $query) {
	}
	
	//-------------------------------------
	// SELECT結果データの更新
	public function after_select ( & $ts, $query) {
	}
	
	//-------------------------------------
	// Query実行(全件取得)
	public function select ($query) {
		
		$this->before_select($query);
		
		$ts =dbi()->select($query);
		
		$this->after_select($ts,$query);
		
		return $ts;
	}
	
	//-------------------------------------
	// Query実行(1件のデータ取得)
	public function select_one ($query) {
		
		$this->before_select($query);
		
		$t =dbi()->select_one($query);
		
		$this->after_select(array($t),$query);
		
		return $t;
	}
	
	//-------------------------------------
	// Query実行(件数取得)
	public function select_count ($query) {
		
		$this->before_select($query);
		
		$count =dbi()->select_count($query);
		
		return $count;
	}
	
	//-------------------------------------
	// Query実行(Pager取得)
	public function select_pager ($query) {
		
		$this->before_select($query);
		
		$p =dbi()->select_pager($query);
		
		return $p;
	}
	
	//-------------------------------------
	// Query実行(INSERT)
	public function insert ($query) {
		
		$this->before_create($query);
		
		$id =dbi()->insert($query)
				? dbi()->last_insert_id() 
				: null;
		
		return $id;
	}
	
	//-------------------------------------
	// Query実行(UPDATE)
	public function update ($query) {
		
		$this->before_select($query);
		$this->before_update($query);
		
		$r =dbi()->update($query);
		
		return $r;
	}
	
	//-------------------------------------
	// Query実行(DELETE)
	public function delete ($query) {
		
		$this->before_select($query);
		$this->before_delete($query);
		
		$r =dbi()->update($query);
		
		return $r;
	}
}
