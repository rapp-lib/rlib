<?php

namespace R\Lib\Rapper;
use R\Lib\Core\Arr;
use R\Lib\Core\String;
use R\Lib\Core\ClassLoader;
use R\Lib\Core\Profiler;

/**
 * 自動生成エンジン
 */
class Rapper 
{
	/**
	 * schemaレジストリ
	 * @var array
	 */
	protected $schema =array();

	/**
	 * deployレジストリ
	 * @var array
	 */
	protected $deploy =array();
	
	/**
	 * 登録されているModのリスト
	 * @var array
	 */
	protected $mods =array();

	/**
	 * 登録されているFilterのリスト
	 * @var array
	 */
	protected $filters =array();
	
	/**
	 * install()処理中のmod_id
	 * @var [type]
	 */
	private $installModId;
	
	/**
	 * $installModIdの退避用スタック
	 * @var [type]
	 */
	private $installModIdStack =array();
	
	/**
	 * schemaレジストリ操作
	 */
	public function & schema ($name=null, $value=null) {
		
		return array_registry($this->schema,$name,$value);
	}
	
	/**
	 * deployレジストリ操作
	 */
	public function & deploy ($name=null, $value=null) {
		
		return array_registry($this->deploy,$name,$value,array("escape"=>true));
	}
	
	/**
	 * Modのインストール
	 */
	public function require_mod ($mod_id) 
	{
		// 登録済みであればinstall()処理はスキップ
		if ($this->mods[$mod_id]) {
			return;
		}

		// Modのインスタンス生成
		$mod_class ='R\\Lib\\Rapper\\Mod\\'.String::camelize($mod_id);
		$mod =new $mod_class($this);
		$this->mods[$mod_id] =$mod;
		
		// Mod->install()の呼び出し
		array_push($this->installModIdStack, $this->installModId);
		$this->installModId =$mod_id;
		$mod->install();
		$this->installModId =array_pop($this->installModIdStack);
	}
	
	/**
	 * Filterの登録
	 */
	public function add_filter ($type, $options, $func) 
	{
		if ( ! $this->installModId) {
			report_error("Mod->install()外ではfilter登録できません");
		}

		$filter =$options;
		$filter["id"] =Profiler::getFunctionId($func);
		$filter["type"] =$type;
		$filter["func"] =$func;
		$filter["mod_id"] =$this->installModId;
		
		if ($filter_exists =$this->filters[$filter["type"]][$filter["id"]]) {
			report_error("同名のfilterが登録されています",array(
				"filter_id" =>$filter["id"],
				"filter_1" =>$filter_exists,
				"filter_2" =>$filter,
			));
		}
		
		$this->filters[$filter["type"]][$filter["id"]] =$filter;
	}
	
	/**
	 * Filterの適用
	 */
	public function apply_filters ($type, $data=null) 
	{
		foreach ((array)$this->filters[$type] as $filter) {
			// 適用条件判定
			foreach ((array)$filter["cond"] as $k => $v) {
				if (is_int($k)) {
					if (Arr::ref($data,$v) === null) { continue 2; }
				} else {
					if (Arr::ref($data,$k) != $v) { continue 2; }
				}
			}
			
			// 適用
			$data_result =$filter["func"]($this, $data);
			
			if ($data_result !== null) {
				$data =$data_result;
			}
		}

		return $data;
	}

	/**
	 * Schemaに対応するSchemaオブジェクトを生成
	 * @param  [type] $si [description]
	 * @return [type]     [description]
	 */
	public function factorySchemaObject ($si, $schema_item) 
	{
		$schemaClass ='R\\Lib\\Rapper\\Schema\\'.String::camelize($si);
		if ( ! class_exists($schemaClass)) {
			$schemaClass ='R\\Lib\\Rapper\\Schema\\General';
		}
		$schemaObject =new $schemaClass($schema_item["_id"],$schema_item);
		return $schemaObject;
	}
}
