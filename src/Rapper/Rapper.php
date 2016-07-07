<?php 

namespace R\Lib\Rapper;
use R\Lib\Core\String;
use R\Lib\Core\ClassLoader;
use R\Lib\Core\Report;
use R\Lib\Core\Profiler;

/**
 * 自動生成エンジン
 */
class Rapper 
{
	/**
	 * [$schema description]
	 * @var array
	 */
	protected $schema =array();

	/**
	 * [$deploy description]
	 * @var array
	 */
	protected $deploy =array();
	
	/**
	 * [$installModId description]
	 * @var [type]
	 */
	protected $installModId;
	
	/**
	 * [$mods description]
	 * @var array
	 */
	protected $mods =array();

	/**
	 * [$filters description]
	 * @var array
	 */
	protected $filters =array();
	
	/**
	 * 
	 */
	public function mod ($mod_id)
	{
		return $this->mods[$mod_id];
	}
	
	/**
	 * 
	 */
	public function & schema ($name=null, $value=null) {
		
		return array_registry($this->schema,$name,$value);
	}
	
	/**
	 * 
	 */
	public function & deploy ($name=null, $value=null) {
		
		return array_registry($this->deploy,$name,$value,array("escape"=>true));
	}
	
	/**
	 * 
	 */
	public function require_mod ($mod_id) 
	{
		if ($this->mods[$mod_id]) {
			return;
		}

		$mod_class ='R\\Lib\\Rapper\\Mod\\'.String::camelize($mod_id);
		$mod =new $mod_class($this);
		$this->mods[$mod_id] =$mod;
		
		// Mod\*->install
		$this->installModId =$mod_id;
		$mod->install();
		$this->installModId =null;
	}
	
	/**
	 * 
	 */
	public function add_filter ($type, $options, $func) 
	{
		if ( ! $this->installModId) {
			Report::error("Mod->install()外ではfilter登録できません");
		}

		$filter =$options;
		$filter["id"] =Profiler::getFunctionId($func);
		$filter["type"] =$type;
		$filter["func"] =$func;
		$filter["mod_id"] =$this->installModId;
		
		if ($filter_exists =$this->filters[$filter["type"]][$filter["id"]]) {
			Report::error("同名のfilterが登録されています",array(
				"filter" =>$filter,
				"filter_exists" =>$filter_exists,
			));
		}
		
		$this->filters[$filter["type"]][$filter["id"]] =$filter;
	}
	
	/**
	 * 
	 */
	public function apply_filters ($type, $data=null) {
		
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
	 * 
	 */
	public function parse_schema_csv_file ($schema_csv_file) 
	{
		$parser =new SchemaCsvParser;
		$schema =$parser->parse_schema_csv($schema_csv_file);
		return $schema;
	}
	
	/**
	 * 
	 */
	public function label ($schema_index, $id) 
	{
		// 配列指定時には要素を処理して返す
		if (is_array($id)) {
			$labels =array();
			foreach ($id as $k=>$v) {
				$labels[$k] =$this->label($schema_index,$v);
			}
			return $labels;
		}
		
		$label =$this->schema($schema_index.".".$id.".label");
		return strlen($label) ? $label : $id;
	}
	
	/**
	 * 
	 */
	public function parse_php_tmpl ($tmpl_file_path,$tmpl_vars) 
	{
		$tmpl_asset_path ="asset/rapper_tmpl/".$tmpl_file_path;
		$tmpl_file =ClassLoader::findAsset('R\\Lib\\Rapper',$tmpl_asset_path);

		// tmpl_fileの検索
		if ( ! $tmpl_file) {
			Report::error("テンプレートファイルがありません",array(
				"tmpl_file" =>$tmpl_asset_path,
			));
		}
		
		// tmpl_varsのアサイン
		$r =$this;
		extract($tmpl_vars,EXTR_REFS);
		
		ob_start();
		include($tmpl_file);
		$src =ob_get_clean();
		$src =str_replace('<!?','<?',$src);
		
		return $src;
	}
}
