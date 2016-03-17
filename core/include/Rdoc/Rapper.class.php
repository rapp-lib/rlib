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
    
    protected $schema =array();
    protected $deploy =array();
    
    protected $current_mod;
    protected $mods =array();
    protected $filters =array();
    
    protected $registered_method =array();
        
    /**
     * 初期化
     */
    public function __construct () {
    }
    
    /**
     * 
     */
    public function __call ($method_name, $args) {
        
        if ($callback =$this->registered_method[$method_name]) {
            
            array_unshift($args, $this);
            return call_user_func_array($callback, $args);
        
        } else {
            
            report_error("メソッドが登録されていません",array(
                "method_name" =>$method_name,
                "registered_method" =>array_keys($this->registered_method),
            ));
        }
    }
    
    /**
     * 
     */
    public function register_method ($method_name, $callback) {
        
        $this->registered_method[$method_name] =$callback;
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
    public function require_mod ($mod_id) {
        
        // 適用中のModに関連付ける
        $this->current_mod["mods"][$mod_id] =$mod_id;
        
        // 適用処理は1回のみ実行する
        if ($this->mods[$mod_id]) {
        
            return;
        }
        
        $this->mods[$mod_id] =array(
            "id" =>$mod_id,
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
        $filter["id"] =$filter["id"] ? $filter["id"] 
                : $type."-".substr(md5(rand(0,999999)),0,8);
        $filter["type"] =$type;
        $filter["func"] =$func;
        $filter["mod_id"] =$this->current_mod["id"];
        
        // 同名のフィルタが追加された場合エラー
        if ($filter_exists =$this->filters[$type][$filter["id"]]) {
        
            report_error("同名のfilterが登録されています",array(
                "filter" =>$filter,
                "filter_exists" =>$filter_exists,
            ));
        }
            
        // 適用中のModに関連付ける
        $this->current_mod["filters"][$filter["type"]][$filter["id"]] =$filter;
        
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
    
    /**
     * 
     */
    public function parse_schema_csv_file ($schema_csv_file) {
        
        $parser =new SchemaCsvParser;
        $schema =$parser->parse_schema_csv($schema_csv_file);
        return $schema;
    }
    
    /**
     * 
     */
    public function label ($schema_index, $id) {
        
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
    public function parse_php_tmpl ($tmpl_file_path,$tmpl_vars) {
        
        // tmpl_fileの検索
        $tmpl_file =find_include_path("modules/rapper_tmpl/".$tmpl_file_path);
        
        if ( ! $tmpl_file) {
            
            report_error("テンプレートファイルがありません",array(
                "tmpl_file" =>"modules/rapper_tmpl/".$tmpl_file_path,
            ));
        }
        
        // tmpl_varsのアサイン
        extract($tmpl_vars,EXTR_REFS);
        
        ob_start();
        include($tmpl_file);
        $src =ob_get_clean();
        $src =str_replace('<!?','<?',$src);
        
        return $src;
    }
}    
