<?php

//-------------------------------------
// Model基本クラス
class Model_App extends Model_Base {
	
	//-------------------------------------
	// SELECT/DELETE/UPDATEの前処理（table,conditionsを対象）
	public function before_read ( & $query) {
	
		parent::before_read($query);
	}
	
	//-------------------------------------
	// INSERT/UPDATEの前処理（table,fieldsを対象）
	public function before_write ( & $query) {
	
		parent::before_write($query);
	}
	
	//-------------------------------------
	// SELECTの前処理
	public function before_select ( & $query) {
	
		parent::before_select($query);
	}
	
	//-------------------------------------
	// INSERTの前処理
	public function before_insert ( & $query) {
	
		parent::before_insert($query);
	}
	
	//-------------------------------------
	// UPDATEの前処理
	public function before_update ( & $id, & $query) {
	
		parent::before_update($id,$query);
	}
	
	//-------------------------------------
	// DELETEの前処理
	public function before_delete ( & $id, & $query) {
	
		parent::before_delete($id,$query);
	}
	
	//-------------------------------------
	// SELECTの後処理（tsを対象）
	public function after_read ( & $ts, & $query) {
	
		parent::after_read($ts,$query);
	}
	
	//-------------------------------------
	// INSERT/UPDATE/DELETEの後処理（idを対象）
	public function after_write ( & $id, & $query) {
	
		parent::after_write($id,$query);
	}
	
	//-------------------------------------
	// SELECTの後処理
	public function after_select ( & $ts, & $query) {
	
		parent::after_select($ts,$query);
	}
	
	//-------------------------------------
	// INSERTの後処理
	public function after_insert ( & $id, & $query) {
	
		parent::after_insert($id,$query);
	}
	
	//-------------------------------------
	// UPDATEの後処理
	public function after_update ( & $id, & $query) {
	
		parent::after_update($id,$query);
	}
	
	//-------------------------------------
	// DELETEの後処理
	public function after_delete ( & $id, & $query) {
		
		parent::after_delete($id,$query);
	}
}
