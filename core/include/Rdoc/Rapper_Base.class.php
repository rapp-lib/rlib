<?php 

/**
 * 自動生成エンジンを構成する機能セット
 */
class Rapper_Base {
    
    protected $config =array();
    protected $schema =array();
    protected $deployee =array();
    
    protected $current_mod;
    protected $mods =array();
    protected $filters =array();
    
    /**
     * 
     */
    public function get_config ($name) {
        
        return array_ref($this->config,$name);
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
    public function add_deployee ($deployee) {
        
        $this->deployee[] =$deployee;
    }
    
    /**
     * 全展開対象の取得
     */
    public function get_deployee ($key) {
        
        return $key
                ? $this->deployee[$key]
                : $this->deployee;
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
        
        load_module("rapper_mod",$mod_id);
        
        array_pop($this->current_mod_stack);
        
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
    public function add_filter ($type, $filter_id, $conditions, $func) {
        
        // 同名のフィルタが追加された場合エラー
        if ($this->filters[$type][$filter_id]) {
        
            report_error("同名のfilterが登録されています",array(
                "type" =>$type,
                "filter_id" =>$filter_id,
                "mod_id" =>$this->filters[$type][$filter_id]["mod_id"],
                "current_mod_id" =>$this->current_mod["mod_id"],
            ));
        }
            
        // 適用中のModに関連付ける
        $this->current_mod["filters"][$type][$filter_id] =array(
            "filter_id" =>$filter_id,
            "conditions" =>$conditions,
        );
        
        $this->filters[$type][$filter_id] =array(
            "conditions" =>$conditions, 
            "func" =>$func,
            "mod_id" =>$current_mod,
        );
    }
    
    /**
     * 
     */
    public function apply_filters ($type, $data) {
        
        foreach ($this->filters[$type] as $filter) {
            
            // 適用条件判定
            foreach ((array)$filter["conditions"] as $k => $v) {
                
                if ($data[$k] != $v) {
                    
                    continue;
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
