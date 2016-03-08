<?php 

/**
 * 自動生成エンジン
 */
class Rapper extends Rapper_Base {
}

/**
 * 自動生成エンジンを構成する機能セット
 */
class Rapper_Base {
    
    protected $config =array();
    protected $schema =array();
    protected $deploy =array();
    
    protected $current_mod;
    protected $mods =array();
    protected $filters =array();
        
    /**
     * 初期化
     */
    public function __construct () {
    }
    
    /**
     * 
     */
    public function & config ($name=null, $value=null) {
        
        return array_registry($this->config,$name,$value);
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
        
        return array_registry($this->deploy,$name,$value);
    }
	
    /**
     * 
     */
    public function require_mod ($mod_id) {
        
        // 適用中のModに関連付ける
        $this->current_mod["mods"][$mod_id] =$mod_id;
        
        // 適用処理は1回のみ実行する
        if ($this->mods[$mod_id]) {
        
            return;
        }
        
        $this->mods[$mod_id] =array(
            "mod_id" =>$mod_id,
            "mods" =>array(),
            "filters" =>array(),
        );
        
        $this->current_mod = & $this->mods[$mod_id];
        $this->current_mod_stack[] = & $this->mods[$mod_id];
        
        $module =load_module("rapper_mod",$mod_id);
        call_user_func($module, $this);
        
        if ($this->current_mod_stack) {
            
            $last_index =count($this->current_mod_stack)-1;
            $this->current_mod_stack = & $this->current_mod_stack[$last_index];
            unset($this->current_mod_stack[$last_index]);
            
        } else {
            
            unset($this->current_mod);
        }
    }
	
    /**
     * 
     */
    public function add_filter ($type, $options, $func) {
        
        $filter =$options;
        $filter["filter_id"] =$filter["filter_id"] ? $filter["filter_id"] 
                : $type."-".substr(md5(serialize($filter)),0,8);
        $filter["type"] =$type;
        $filter["func"] =$func;
        $filter["mod_id"] =$this->current_mod;
        
        // 同名のフィルタが追加された場合エラー
        if ($filter_exists =$this->filters[$type][$filter["filter_id"]]) {
        
            report_error("同名のfilterが登録されています",array(
                "filter" =>$filter,
                "filter_exists" =>$filter_exists,
            ));
        }
            
        // 適用中のModに関連付ける
        $this->current_mod["filters"][$filter["type"]][$filter["filter_id"]] =$filter;
        
        $this->filters[$filter["type"]][$filter["filter_id"]] =$filter;
    }
    
    /**
     * 
     */
    public function apply_filters ($type, $data=null) {
        
        foreach ((array)$this->filters[$type] as $filter) {
            
            // 適用条件判定
            foreach ((array)$filter["cond"] as $k => $v) {
                
                if (is_int($k)) {
                
                    if (ref_array($data,$v) === null) { continue 2; }
                    
                } else {
                
                    if (ref_array($data,$k) != $v) { continue 2; }
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
}    
