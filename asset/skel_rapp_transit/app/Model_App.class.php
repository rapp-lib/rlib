<?php

/**
 * @model
 */
class Model_App extends Model_Base 
{
	/**
	 * SELECT/DELETE/UPDATEの前処理（table,conditionsを対象）
	 * @override
	 */
	public function before_read ( & $query) 
	{
		parent::before_read($query);
	}
	
	/**
	 * INSERT/UPDATEの前処理（table,fieldsを対象）
	 * @override
	 */
	public function before_write ( & $query) 
	{
		parent::before_write($query);
	}
	
	/**
	 * SELECTの後処理（tsを対象）
	 * @override
	 */
	public function after_read ( & $ts, & $query) 
	{
		parent::after_read($ts,$query);
	}
	
	/**
	 * INSERT/UPDATE/DELETEの後処理（idを対象）
	 * @override
	 */
	public function after_write ( & $id, & $query) 
	{
		parent::after_write($id,$query);
	}
}
