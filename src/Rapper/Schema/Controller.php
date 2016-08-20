<?php

namespace R\Lib\Rapper\Schema;

/**
 * 
 */
class Controller extends BaseSchema
{
	/**
	 * 
	 */
	public function getClassName()
	{
		return String::camelize($this->id)."Controller";
	}

	/**
	 * 
	 */
	public function getActions()
	{\report($this->schema);
		return $this->schema["actions"];
	}

	/**
	 * 
	 */
	public function getAction($actionName)
	{
		return $this->schema["actions"][$actionName];
	}

	/**
	 * [getFields description]
	 * @param  [type] $c_id [description]
	 * @param  [type] $type [description]
	 * @return [type]       [description]
	 */
	public function getFields (){
		
		$table =$a["table"] ? $a["table"] : ($c["table"] ? $c["table"] : "");
		$rel_id =$a["rel"] ? $a["rel"] : ($c["rel"] ? $c["rel"] : "default");
		
		// 関連するtableがなければ対象外
		if ( ! $table) { continue; }
		
		foreach ((array)$r->schema("col.".$table) as $col_id => $col) {
			
			// 関係する入力項目（rel=input|required）以外を除外
			$rel =$col["rels"][$rel_id];
			if ($rel != "input" && $rel != "required") { continue; }
		}
	}
}